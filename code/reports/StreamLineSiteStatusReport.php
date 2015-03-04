<?php

class StreamLineSiteStatusReport extends SS_Report
{
    public function title()
    {
        return 'Site Status';
    }

    public function sourceRecords($params = null)
    {
        $modules = StreamLineSiteStatusModule::get()->sort('ModuleName');

        if (!$modules->count()) {
            $this->buildModuleIndex();
        } else {
            $this->updateModuleIndex();
        }

        return StreamLineSiteStatusModule::get()->sort('ModuleName');
    }

    public function columns() {
        $fields = array(
            'ModuleName' => 'Name',
            'CurrentVersion' => 'Current Version',
            'LatestVersion' => 'Latest Version',
            'Stable' => 'Stable',
            'UpToDate' => 'Up to Date'
        );

        return $fields;
    }

    private function buildModuleIndex()
    {
        // Read the composer.lock file to get composer package info
        $composer = json_decode(file_get_contents(dirname(dirname(dirname(__DIR__))) . '/composer.lock'), true);
        $packages = array();

        foreach($composer['packages'] as $package) {
            if (strpos($package['name'], 'composer') === false) {
                $packages[] = array(
                    'Name' => $package['name'],
                    'CurrentVersion' => $package['version'],
                    'Git' => $package['source']['url'],
                    'Stable' => $this->isModuleStable($package['version']),
                );
            }
        }

        $base_url = 'https://api.github.com/repos/';

        // Query github to get the latest info
        foreach($packages as $idx => $package) {
            preg_match('#https:\/\/github.com\/([^\.]+)\.git#', $package['Git'], $matches);
            $url = $base_url . $matches[1];

            $client = new RestfulService($url);
            $tag_request = $client->request('/tags', 'GET');
            $tags = json_decode($tag_request->getBody(), true);

            $versions = array();

            foreach ($tags as $tag) {
                $versions[] = $tag['name'];
            }

            $latest_version = null;

            foreach ($versions as $version) {
                if (!preg_match('#(alpha|beta|rc|dev|a|b)#', $version)) {
                    $version = preg_replace('#(patch[0-9]+)#', '', $version);
                    $version = str_replace(array('-', 'v'), '', $version);

                    if (version_compare($version, $package['CurrentVersion'], '>')) {
                        if (empty($latest_version)) {
                            $latest_version = $version;
                        } else {
                            if (version_compare($version, $latest_version, '>')) {
                                $latest_version = $version;
                            }
                        }
                    }
                }
            }

            if (empty($latest_version)) {
                $latest_version = $package['CurrentVersion'];
            }

            $package['LatestVersion'] = $latest_version;
            $package['UpToDate'] = ($package['CurrentVersion'] == $package['LatestVersion']) ? true : false;

            $module = StreamLineSiteStatusModule::create();
            $module->ModuleName = $package['Name'];
            $module->CurrentVersion = $package['CurrentVersion'];
            $module->LatestVersion = $package['LatestVersion'];
            $module->UpToDate = $package['UpToDate'];
            $module->Stable = $package['Stable'];
            $module->Git = $package['Git'];
            $module->write();
        }
    }

    private function updateModuleIndex()
    {
        $modules = StreamLineSiteStatusModule::get();

        $base_url = 'https://api.github.com/repos/';

        foreach($modules as $module) {
            preg_match('#https:\/\/github.com\/([^\.]+)\.git#', $module->Git, $matches);
            $url = $base_url . $matches[1];

            $client = new RestfulService($url);
            $tag_request = $client->request('/tags', 'GET');
            $tags = json_decode($tag_request->getBody(), true);

            $versions = array();

            foreach ($tags as $tag) {
                $versions[] = $tag['name'];
            }

            $latest_version = null;

            foreach ($versions as $version) {
                if (!preg_match('#(alpha|beta|rc|dev|a|b)#', $version)) {
                    $version = preg_replace('#(patch[0-9]+)#', '', $version);
                    $version = str_replace(array('-', 'v'), '', $version);

                    if (version_compare($version, $module->CurrentVersion, '>')) {
                        if (empty($latest_version)) {
                            $latest_version = $version;
                        } else {
                            if (version_compare($version, $latest_version, '>')) {
                                $latest_version = $version;
                            }
                        }
                    }
                }
            }

            if (empty($latest_version)) {
                $latest_version = $module->CurrentVersion;
            }

            $module->LatestVersion = $latest_version;
            $module->UpToDate = ($module->CurrentVersion == $module->LatestVersion) ? true : false;
            $module->write();
        }
    }

    private function isModuleStable($version)
    {
        $regex = '#v?[0-9]+(\.[0-9]+)?(\.[0-9]+)?(-([a-z]+))?#i';
        preg_match($regex, $version, $matches);

        if (isset($matches[5])) {
            switch (strtolower($matches[5])) {
                case 'alpha':
                case 'a':
                    return false;
                    break;

                case 'beta':
                case 'b':
                    return false;
                    break;

                case 'rc':
                    return false;
                    break;

                case 'dev':
                    return false;
                    break;

                default:
                    return true;
            }
        }

        if ($version == 'dev-master') {
            return false;
        }

        return true;
    }
}