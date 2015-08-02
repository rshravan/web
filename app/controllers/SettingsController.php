<?php namespace Controllers;

use View, Redirect, Input, Config, Response;
use Repositories\ImageHandler\ImageHandlerInterface as ImageHandlerInterface;
use Repositories\ConfigReader\ConfigReaderInterface as ConfigReaderInterface;

class SettingsController extends BaseController
{
    public function __construct(ImageHandlerInterface $imageHandler, 
        ConfigReaderInterface $reader)
    {
        parent::__construct($imageHandler, $reader);
        $this->imageHandler = $imageHandler;
        $this->reader = $reader;
        $this->config = Config::get("app.config");
    }
    
    /********************************************
     *  Show the settings page.
     */ 

    public function index()
    {
        $directory = $this->config;
        $settings = $this->reader->parse($directory)["instance"]["children"];

        $days = $this->imageHandler->getDays(5);

        return View::make('settings',
        [
            'days' => $days, 
            'settings' => $settings
        ]);
    }

    /********************************************
     *  Show the cloud page.
     */ 
    public function cloud()
    {
        $directory = $this->config;
        $settings = $this->reader->parse($directory)["amazonS3"]["children"];

        $days = $this->imageHandler->getDays(5);

        return View::make('cloud',
        [
            'days' => $days, 
            'settings' => $settings
        ]);
    }

    /******************************************************************
     *  Get new settings from the settings page.
     *
     *      - update the configuration files of a certain directory
     *      with the correct settings.
     */ 

    public function update()
    {
        $settings = Input::except("_token");

        foreach($settings as $name => $setting)
        {
            // ----------------------------------------------------------------------
            // There can be multiple dropdowns (options), we should concatenate them

            $version = explode(":", $name);
            if(count($version) > 1)
            {
                // ----------------
                // Remove old name

                unset($settings[$name]);

                // ----------------
                // Create new name

                $version = $version[count($version)-1];
                $name = rtrim($name, ":" . $version);

                if(array_key_exists($name, $settings)) 
                {
                    $settings[$name] .= "," . $setting;
                }
                else
                {
                    $settings[$name] = $setting;
                }
            }
        }
        
        $this->reader->save($this->config, $settings);

        return Redirect::back();
    }
    
    /******************************************************************
     *  Edit a piece of a configuration file.
     */ 
    private function getPiece($config, array $keys)
    {
        $settings = $this->reader->read($this->config . '/'. $config);
        
        foreach($keys as $key)
        {
            if($settings->$key)
            {
                $settings = &$settings->$key;   
            }
        }
        
        if($settings)
        {
            return $settings;
        }
        
        return null;
    }
    
    /*******************************************
    *   Get and set name of the kerberos instance
    */
    
    public function getName()
    {
        $instance["name"] = $this->getPiece("config.xml", ["instance", "name"])->__toString();
        return Response::json($instance);
    }
    
    public function updateName()
    {
        if(Input::get('name') != '')
        {
            $settings["config__instance__name"] = Input::get('name');
            
            $this->reader->save($this->config, $settings);
        }
        
        return $this->getName();
    }
    
    /*******************************************
    *   Get and set the "Enabled" condition.
    */
    
    public function getConditionEnabled()
    {
        $instance = $this->getPiece("condition.xml", ["Enabled"]);
        return Response::json($instance);
    }
    
    public function updateConditionEnabled()
    {
        if(Input::get('active') != '')
        {
            $settings["condition__Enabled__active"] = Input::get('active');
        }
        
        if(Input::get('delay') != '')
        {
            $settings["condition__Enabled__delay"] = Input::get('delay');
        }
        
        $this->reader->save($this->config, $settings);
        
        return $this->getConditionEnabled();
    }
}
