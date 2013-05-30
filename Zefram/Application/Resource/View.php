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
 *   resources.view.translator =
 *   resources.view.headTitle.title =
 *   resources.view.headTitle.separator =
 *   resources.view.headTitle.defaultAttachOrder =
 *
 * Options providing Zend_Application_Resource_View functionality: 
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

        // postpone setting of charset until suitable doctype is set
        if (array_key_exists('charset', $options)) {
            $charset = $options['charset'];
            unset($options['charset']);

            if (isset($options['doctype'])) {
                $options['charset'] = $charset;
            }
        }

        $viewClass = $this->_class;
        $this->_view = new $viewClass($options);

        parent::__construct($options);
    }

    public function init()
    {
        $view = $this->getView();
        $this->getViewRenderer()->setView($view);

        return $view;
    }

    public function getViewRenderer()
    {
        return Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
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
        if (!$this->_view->doctype()->isHtml5()) {
            throw new Zend_View_Exception('Meta charset tag requires an HTML5 doctype');
        }
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
                    // invalid resource name or circular dependency detected
                    $translate = null;
                }
            }
        }

        if ($translate && ($helper = $this->_view->getHelper('translate'))) {
            $helper->setTranslator($translate);
        }

        return $this;
    }

    public function setScriptPathSpec($path)
    {
        $this->getViewRenderer()->setViewScriptPathSpec($path);
        return $this;
    }

    public function setScriptPathNoControllerSpec($path)
    {
        $this->getViewRenderer()->setViewScriptPathNoControllerSpec($path);
        return $this;
    }

    public function setSuffix($suffix)
    {
        $this->getViewRenderer()->setViewSuffix($suffix);
        return $this;
    }
}
