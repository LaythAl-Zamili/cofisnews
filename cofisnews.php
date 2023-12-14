<?php

class CofisNews extends Module
{
    // Module configuration parameters
    private $feedUrl = 'https://cofis.cz/feed/';
    private $articleCount = 10;
    private $cacheTTL = 86400;

    public function __construct()
    {
        // Module information
        $this->name = 'cofisnews';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Layth Al-Zamili';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        // Module display name and description
        $this->displayName = $this->l('Cofis News');
        $this->description = $this->l('Display the latest articles from the blog on the dashboard.');

    
        parent::__construct();

        // $this->registerHooks();
    }

    // Register the hooks
    private function registerHooks()
    {
        $hooks = array(
            'actionAdminControllerSetMedia',
            'dashboardData',
            'dashboardZoneOne',
            'displayAdminStatsModules',
            'displayAdminDashboard'
        );

        // Register each hook
        foreach ($hooks as $hook) {
            $this->registerHook($hook);
        }
    }

    // Install the module
    public function install()
    {
        return parent::install() && $this->registerHooks();
    }

    // Uninstall the module
    public function uninstall()
    {
        return parent::uninstall();
    }

    // Get feed content from the specified URL
    private function getFeedContent()
    {
        $cacheKey = 'cofisnews_feed';

        // Check if cached data is still valid
        if ($this->isCacheValid($cacheKey)) {
            return $this->getCache($cacheKey);
        }

        $feedContent = '';

        try {
            // Attempt to get feed content from the URL
            $feedContent = Tools::file_get_contents($this->feedUrl, false, null, 1);
        } catch (Exception $e) {
            // Handle any exceptions or,if the feed cannot be loaded or something like that
            return false;
        }

        // Parse the feed content and set the cache
        $parsedFeed = $this->parseFeed($feedContent, $this->articleCount);

        if ($parsedFeed) {
            $this->setCache($cacheKey, $parsedFeed, $this->cacheTTL);
        }

        return ['feedContent' => $parsedFeed];
    }

    // Parse the feed content and extract necessary information
    private function parseFeed($feedContent, $articleCount)
    {
        $parsedFeed = array();

        try {
            // Create a SimXML.. from the feed content
            $xml = new SimpleXMLElement($feedContent);
            $items = $xml->channel->item;

            $count = 0;
            foreach ($items as $item) {
                // Extract title and link from each item
                $title = (string) $item->title;
                $link = (string) $item->link;

                // Add UTM tags to the link
                $shopUrl = urldecode(Context::getContext()->shop->getBaseURL());
                $link .= '?utm_source=' . urlencode($shopUrl);
                $link .= '&utm_medium=cofisczcofisnews-dashboard';

                // Add the parsed data to the array
                $parsedFeed[] = array(
                    'title' => $title,
                    'link' => $link,
                );

                $count++;
                if ($count >= $articleCount) {
                    break;
                }
            }
        } catch (Exception $e) {
            // Handle if any errors later
            return false;
        }

        return $parsedFeed;
    }

    // Check if the cache is still valid
    private function isCacheValid($cacheKey)
    {
        $lastUpdate = (int) Configuration::get($cacheKey . '_last_update');
        return ($lastUpdate + $this->cacheTTL) > time();
    }

    // Get data from the cache
    private function getCache($cacheKey)
    {
        return json_decode(Configuration::get($cacheKey), true);
    }

    // Set data in the cache
    private function setCache($cacheKey, $data, $cacheTTL)
    {
        Configuration::updateValue($cacheKey, json_encode($data));
        Configuration::updateValue($cacheKey . '_last_update', time());
    }

    // Hook to include JavaScript in the back office
    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addJS($this->_path . 'views/js/cofisnews_admin.js');
    }

    // Hook to provide data for the dashboard
    public function hookDashboardData()
    {
        $templateFile = 'module:' . $this->name . '/views/templates/admin/dashboard_data.tpl';

        // Check if the template is not already cached
        if (!$this->isCached($templateFile, $this->getCacheId($this->name))) {
            // Get the feed content
            $feedContent = $this->getFeedContent();

            // Assign data to Smarty variables
            if ($feedContent === false) {
                // When the feed cannot be loaded
                $this->context->smarty->assign('feedError', $this->l('Feed cannot be loaded'));
            } else {
                // Get shop URL
                $shopUrl = urlencode(Context::getContext()->shop->getBaseURL());

                // UTM parameters
                $feedContent['utm_source'] = $shopUrl;
                $feedContent['utm_medium'] = 'cofisczcofisnews-dashboard';

                // Assign data to Smarty
                $this->context->smarty->assign('feedContent', $feedContent);
            }
        }

        // Return data for the dashboard
        return [
            'cofisnews' => $this->fetch($templateFile, $this->getCacheId($this->name))
        ];
    }

    // Hook to display content in the first zone of the dashboard
    public function hookDashboardZoneOne($params)
    {
        $templateFile = 'module:' . $this->name . '/views/templates/admin/dashboard_zone_one.tpl';

        // Check if the template is not already cached
        if (!$this->isCached($templateFile, $this->getCacheId($this->name))) {
            // Get the feed content
            $feedContent = $this->getFeedContent();

            // Assign data to Smarty variables
            if ($feedContent) {
                $this->context->smarty->assign('feedContent', $feedContent);
            } else {
                // No need for now
            }
        }

        // Return content for the dashboard zone
        return $this->fetch($templateFile, $this->getCacheId($this->name));
    }

    // Hook to display content in the "Stats" tab of the admin modules page
    public function hookDisplayAdminStatsModules($params)
    {
        $templateFile = 'module:' . $this->name . '/views/templates/admin/stats_modules.tpl';

        // Check if the template is not already cached
        if (!$this->isCached($templateFile, $this->getCacheId($this->name))) {
            // Get the feed content
            $feedContent = $this->getFeedContent();

            // Assign data to that variables
            if ($feedContent) {
                $this->context->smarty->assign('feedContent', $feedContent);
            } else {
                // No need for now
            }
        }

        // Return content for the "Stats" tab
        return $this->fetch($templateFile, $this->getCacheId($this->name));
    }

    // Hook to display content on the admin dashboard
    public function hookDisplayAdminDashboard($params)
    {
        // Get the feed content
        $feedContent = $this->getFeedContent();

        // Assign data to Smarty variable
        if ($feedContent) {
            $this->context->smarty->assign('feedContent', $feedContent);

        }

        // No need for now
        // return $this->display(__FILE__, 'views/templates/admin/error.tpl');
    }
}
