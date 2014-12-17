<?php

/**
 * View helper to render scripts across different modules.
 *
 * This implementation has two advantages over Partial view helper (ZF 1.12.3):
 * - it does not clone view to render script
 * - it correctly determines module views directory based on the output of
 *   Zend_Controller_Action_Helper_ViewRenderer::getViewBasePathSpec(), not
 *   by using a hard-coded directory name
 * - works in the current scope
 * - script file extension, if not present, will be appended
 *
 * @package Maniple_View
 * @version 2014-12-17
 * @author  xemlock
 */
class Zefram_View_Helper_RenderScript extends Zend_View_Helper_Abstract
{
    /**
     * Render a given view script.
     *
     * If no arguments are passed, returns the helper instance.
     *
     * @param  string $script
     * @param  string|array $module OPTIONAL
     * @param  array $vars OPTIONAL
     * @return string|Zefram_View_Helper_RenderScript
     */
    public function renderScript($script = null, $module = null, array $vars = null)
    {
        if (func_get_args() === 0) {
            return $this;
        }

        if (is_array($module)) {
            $vars = $module;
            $module = null;
        }

        $view = $this->view;
        $viewState = null;

        // if module name is given setup base path
        if ($module !== null) {
            $viewRenderer = $this->getViewRenderer();
            $request = $viewRenderer->getRequest();

            $origModule = $request->getModuleName();
            $request->setModuleName($module);

            $moduleDir = $viewRenderer->getModuleDirectory();

            // restore original module name
            $request->setModuleName($origModule);

            // base path is built without using inflector as this method is
            // intended for inline template use only
            // (btw, this is how it should be done in Partial view helper,
            // not by hard-coding views/ subdirectory, not by searching for
            // controller directory and taking dirname() of it)
            $viewBasePath = strtr(
                $viewRenderer->getViewBasePathSpec(),
                array(
                    ':moduleDir' => $moduleDir,
                )
            );

            $viewState['scriptPaths'] = $view->getScriptPaths();

            foreach (array('filter', 'helper') as $type) {
                $loader = $view->getPluginLoader($type);
                $viewState['loaders'][$type] = $loader;
                $view->setPluginLoader(clone $loader, $type);
            }

            $view->addBasePath($viewBasePath);
        }

        try {
            $exception = null;

            // assign variables, save overwritten values so that they can be
            // restored during cleanup
            if (is_array($vars)) {
                $viewState['vars'] = array_intersect_key($view->getVars(), $vars);
                $view->assign($vars);
            }

            // render result
            $result = $view->render($this->getScriptName($script));

        } catch (Exception $exception) {
            // will be re-thrown after cleanup
        }

        // restore view state
        if (is_array($vars)) {
            foreach ($vars as $key => $value) {
                unset($view->{$key});
            }
        }

        $this->_setViewState($view, $viewState);

        if ($exception) {
            throw $exception;
        }

        return $result;
    }

    /**
     * This function ensures that given script has proper suffix
     * (i.e. file extension).
     *
     * @param  string $script
     * @return string
     */
    public function getScriptName($script)
    {
        $viewRenderer = $this->getViewRenderer();

        // ensure script has proper suffix (extension)
        if (strpos($viewRenderer->getViewScriptPathSpec(), ':suffix') !== false) {
            $suffix = '.' . ltrim($viewRenderer->getViewSuffix(), '.');
            if (substr($script, -strlen($suffix)) !== $suffix) {
                $script .= $suffix;
            }
        }

        return $script;
    }

    /**
     * @return Zend_Controller_Action_Helper_ViewRenderer
     */
    public function getViewRenderer()
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
    }

    /**
     * Sets view state.
     *
     * @param  Zend_View_Abstract $view
     * @param  array $viewState
     * @return void
     */
    protected function _setViewState(Zend_View_Abstract $view, array $viewState)
    {
        // set variables
        if (isset($viewState['vars'])) {
            $view->assign($viewState['vars']);
        }

        // set script paths
        if (isset($viewState['scriptPaths'])) {
            $view->setScriptPath(null);
            foreach ($viewState['scriptPaths'] as $path) {
                $view->addScriptPath($path);
            }
        }

        // set loaders
        if (isset($viewState['loaders'])) {
            foreach ($viewState['loaders'] as $type => $loader) {
                $view->setPluginLoader($loader, $type);
            }
        }
    }
}
