<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractServiceFactory implements FactoryInterface
{
    protected $ressources;
    protected $language;
    protected $monarcConf = array();

    /**
     * Create Service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return bool
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $class = (property_exists($this, 'class')) ? $this->class : substr(get_class($this), 0, -7);

        if (class_exists($class)) {
            $ressources = $this->getRessources();
            if (empty($ressources)) {
                $instance = new $class();
            } elseif (is_array($ressources)) {
                $sls = array();
                foreach ($ressources as $key => $value) {
                    $sls[$key] = $serviceLocator->get($value);
                }
                $instance = new $class($sls);
            } else {
                $instance = new $class($serviceLocator->get($ressources));
            }

            $instance->setLanguage($this->getDefaultLanguage($serviceLocator));
            $conf = $serviceLocator->get('Config');
            $instance->setMonarcConf(isset($conf['monarc']) ? $conf['monarc'] : array());

            return $instance;
        } else {
            return false;
        }
    }

    /**
     * Get Ressources
     *
     * @return mixed
     */
    public function getRessources()
    {
        return $this->ressources;
    }

    /**
     * Get Default Language
     *
     * @param $sm
     * @return mixed
     */
    public function getDefaultLanguage($sm)
    {
        $config = $sm->get('Config');

        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        return $defaultLanguageIndex;
    }

    /**
     * Get Language
     *
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set Language
     *
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get Monarc Conf
     *
     * @return mixed
     */
    public function getMonarcConf()
    {
        return $this->monarcConf;
    }

    /**
     * Set Monarc Conf
     *
     * @param $conf
     * @return array
     */
    public function setMonarcConf($conf)
    {
        $this->monarcConf = $conf;
        return $this->monarcConf;
    }
}