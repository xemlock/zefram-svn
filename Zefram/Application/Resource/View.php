<?php

/**
 * Resource for settings view options.
 *
 * Additional configuration options:
 *
 *   resources.view.class = "Zend_View"
 *   resources.view.translator =
 *   resources.view.contentType =
 *   resources.view.assign =
 *   resources.view.headTitle.title =
 *   resources.view.headTitle.separator =
 *   resources.view.headTitle.defaultAttachOrder =
 */
class Zefram_Application_Resource_View extends Zend_Application_Resource_ResourceAbstract
{
    protected $_view;
    protected $_class = 'Zend_View';

    public function __construct($options = null)
    {
        if (isset($options['class'])) {
            $this->_class = $options['class'];
            unset($options['class']);
        }

        $viewClass = $this->_class;
        $this->_view = new $viewClass($options);

        parent::__construct($options);
    }

    public function init()
    {
        $view = $this->getView();

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setView($view);

        return $view;
    }

    public function getView()
    {
        return $this->_view;
    }

    public function setDoctype($doctype)
    {
        $this->_view->doctype()->setDoctype(strtoupper($doctype));
        return $this;
    }

    public function setCharset($charset)
    {
        $this->_view->headMeta()->setCharset($charset);
        return $this;
    }

    public function setContentType($contentType)
    {
        $this->_view->headMeta()->appendHttpEquiv('Content-Type', $contentType);
        return $this;
    }

    public function setHttpEquiv($httpEquiv)
    {
        $headMeta = $this->_view->headMeta();

        foreach ((array) $httpEquiv as $key => $value) {
            $headMeta->appendHttpEquiv($key, $value);
        }

        return $this;
    }

    public function setHeadTitle($options)
    {
        $headTitle = $this->_view->headTitle();

        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'title':
                    $headTitle->set($value);
                    break;

                case 'separator':
                    $headTitle->setSeparator($value);
                    break;

                case 'defaultattachorder':
                    $headTitle->setDefaultAttachOrder($value);
                    break;
            }
        }

        return $this;
    }

    public function setTranslator($translate)
    {
        if (is_string($translate)) {
            $bootstrap = $this->getBootstrap();

            // hasPluginResource() creates a resource if it does not exist,
            // but it does not mark it as executed. This may result in an
            // infinite loading loop, especially when there are resources
            // that depend on other resources. To avoid this bootstrap given
            // resource (catch any exceptions) instead of checking for its
            // existence.
            if ($bootstrap instanceof Zend_Application_Bootstrap_ResourceBootstrapper) {
                try {
                    $bootstrap->bootstrap($translate);
                    $translate = $bootstrap->getResource($translate);
                } catch (Zend_Application_Bootstrap_Exception $e) {
                    // invalid resource name or circular resource dependency detected
                    $translate = null;
                }
            }
        }

        if ($translate) {
            $this->_view->setTranslator($translate);
        }

        return $this;
    }
}
