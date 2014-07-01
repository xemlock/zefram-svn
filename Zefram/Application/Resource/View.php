<?php

/**
 * Customizable resource for setting view options.
 *
 * Additional configuration options:
 *
 *   resources.view.class = "Zend_View"
 *   resources.view.scriptPathSpec =
 *   resources.view.scriptPathNoControllerSpec =
 *   resources.view.suffix =
 *   resources.view.noRender =
 *   resources.view.translator =
 *   resources.view.headTitle.title =
 *   resources.view.headTitle.separator =
 *   resources.view.headTitle.defaultAttachOrder =
 *
 * Options originally supported by the Zend_Application_Resource_View:
 *
 *   resources.view.doctype =
 *   resources.view.charset =
 *   resources.view.contentType =
 *
 * Options handled by the Zend_View_Abstract constructor:
 *
 *   resources.view.escape =
 *   resources.view.encoding =
 *   resources.view.basePath =
 *   resources.view.basePathPrefix =
 *   resources.view.scriptPath =
 *   resources.view.helperPath =
 *   resources.view.filterPath =
 *   resources.view.filter =
 *   resources.view.strictVars =
 *   resources.view.lfiProtectionOn =
 *   resources.view.assign =
 *
 *
 * @version 2014-07-01
 * @author xemlock
 */
class Zefram_Application_Resource_View extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Zend_View_Abstract
     */
    protected $_view;

    /**
     * @return Zend_View_Abstract
     */
    public function init()
    {
        $options = $this->getOptions();

        $resourceOptions = array(
            'class'                      => 'Zend_View',
            'charset'                    => null,
            'contentType'                => null,
            'doctype'                    => null,
            'headTitle'                  => null,
            'httpEquiv'                  => null,
            'noRender'                   => null,
            'scriptPathNoControllerSpec' => null,
            'scriptPathSpec'             => null,
            'suffix'                     => null,
            'translator'                 => null,
        );
        foreach ($resourceOptions as $key => $value) {
            if (isset($options[$key])) {
                $resourceOptions[$key] = $options[$key];
            }
        }

        $viewOptions = array_diff_key($options, $resourceOptions);
        $viewClass = $resourceOptions['class'];

        $view = new $viewClass($viewOptions);

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setView($view);

        // Set view script path specification
        if (isset($resourceOptions['scriptPathSpec'])) {
            $viewRenderer->setViewScriptPathSpec($resourceOptions['scriptPathSpec']);
        }

        // Set view script path specification (no controller variant)
        if (isset($resourceOptions['scriptPathNoControllerSpec'])) {
            $viewRenderer->setViewScriptPathNoControllerSpec($resourceOptions['scriptPathNoControllerSpec']);
        }

        // Set view script suffix
        if (isset($resourceOptions['suffix'])) {
            $viewRenderer->setViewSuffix($resourceOptions['suffix']);
        }

        // Set the auto-render flag
        if (isset($resourceOptions['noRender'])) {
            $viewRenderer->setNoRender($resourceOptions['noRender']);
        }

        // Set doctype using doctype view helper
        if (isset($resourceOptions['doctype'])) {
            $view->doctype()->setDoctype(strtoupper($resourceOptions['doctype']));
        }

        // Create an HTML5-style meta charset tag using headMeta view helper
        if (isset($resourceOptions['charset'])) {
            if (!$view->doctype()->isHtml5()) {
                throw new Zend_View_Exception('Meta charset tag requires an HTML5 doctype');
            }
            $view->headMeta()->setCharset($resourceOptions['charset']);
        }

        // Set content-type meta tag using headMeta view helper
        if (isset($resourceOptions['contentType'])) {
            $view->headMeta()->appendHttpEquiv('Content-Type', $resourceOptions['contentType']);
        }

        // Add http-equiv meta tags using headMeta view helper
        if (isset($resourceOptions['httpEquiv'])) {
            foreach ($resourceOptions['httpEquiv'] as $key => $value) {
                $view->headMeta()->appendHttpEquiv($key, $value);
            }
        }

        // Set head title
        if (isset($resourceOptions['headTitle'])) {
            foreach ($resourceOptions['headTitle'] as $key => $value) {
                switch ($key) {
                    case 'title':
                        $view->headTitle()->set($value);
                        break;

                    case 'separator':
                        $view->headTitle()->setSeparator($value);
                        break;

                    case 'defaultAttachOrder':
                        $view->headTitle()->setDefaultAttachOrder($value);
                        break;
                }
            }
        }

        // Set a translation adapter for translate view helper
        // (To avoid duplicated resources or cyclic dependency exceptions
        // the bootstrapping of other resources hast to be done in init())
        if (isset($resourceOptions['translator'])) {
            $translate = $resourceOptions['translator'];

            if (is_string($translate)) {
                $translate = $this->_getBootstrapResource($translate);
            }

            if ($translate) {
                $view->translate()->setTranslator($translate);
            }
        }

        return $this->_view = $view;
    }

    /**
     * Get a resource from bootstrap, initialize it if necessary.
     *
     * @param  string $name
     * @return mixed
     */
    protected function _getBootstrapResource($name)
    {
        $bootstrap = $this->getBootstrap();

        if ($bootstrap->hasResource($name)) {
            $resource = $bootstrap->getResource($name);
        } elseif ($bootstrap->hasPluginResource($name) || method_exists($bootstrap, '_init' . $name)) {
            $bootstrap->bootstrap($name);
            $resource = $bootstrap->getResource($name);
        } else {
            $resource = null;
        }

        return $resource;
    }

    /**
     * Return initialized view object.
     *
     * @return Zend_View_Abstract|null
     */
    public function getView()
    {
        return $this->_view;
    }
}
