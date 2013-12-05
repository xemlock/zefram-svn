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
 *
 *
 * @version 2013-12-05
 * @author xemlock
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

    /**
     * Set doctype using doctype view helper.
     *
     * @param  string $doctype
     * @return Zefram_Application_Resource_View
     */
    public function setDoctype($doctype)
    {
        $this->_view->doctype()->setDoctype(strtoupper($doctype));
        return $this;
    }

    /**
     * Create an HTML5-style meta charset tag using headMeta view helper.
     *
     * @param  string $charset
     * @return Zefram_Application_Resource_View
     * @throws Zend_View_Exception
     */
    public function setCharset($charset)
    {
        if (!$this->_view->doctype()->isHtml5()) {
            throw new Zend_View_Exception('Meta charset tag requires an HTML5 doctype');
        }
        $this->_view->headMeta()->setCharset($charset);
        return $this;
    }

    /**
     * Set content-type meta tag using headMeta view helper.
     *
     * @param  string $contentType
     * @return Zefram_Application_Resource_View
     */
    public function setContentType($contentType)
    {
        $this->_view->headMeta()->appendHttpEquiv('Content-Type', $contentType);
        return $this;
    }

    /**
     * Add http-equiv meta tags using headMeta view helper.
     *
     * @param  array $httpEquiv
     * @return Zefram_Application_Resource_View
     */
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

    /**
     * Set a translation adapter for translate view helper.
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|string $translate
     * @return Zefram_Application_Resource_View
     */
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

    /**
     * Set view script path specification.
     *
     * @param  string $path
     * @return Zefram_Application_Resource_View
     */
    public function setScriptPathSpec($path)
    {
        $this->getViewRenderer()->setViewScriptPathSpec($path);
        return $this;
    }

    /**
     * Set view script path specification (no controller variant).
     *
     * @param  string $path
     * @return Zefram_Application_Resource_View
     */
    public function setScriptPathNoControllerSpec($path)
    {
        $this->getViewRenderer()->setViewScriptPathNoControllerSpec($path);
        return $this;
    }

    /**
     * Set view script suffix.
     *
     * @param  string $suffix
     * @return Zefram_Application_Resource_View
     */
    public function setSuffix($suffix)
    {
        $this->getViewRenderer()->setViewSuffix($suffix);
        return $this;
    }

    /**
     * Set the auto-render flag.
     *
     * @param  bool $flag
     * @return Zefram_Application_Resource_View
     */
    public function setNoRender($flag = true)
    {
        $this->getViewRenderer()->setNoRender($flag);
        return $this;
    }
}
