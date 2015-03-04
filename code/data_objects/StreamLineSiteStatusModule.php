<?php

class StreamLineSiteStatusModule extends DataObject
{
    private static $db = array(
        'ModuleName' => 'Varchar(100)',
        'CurrentVersion' => 'Varchar(15)',
        'LatestVersion' => 'Varchar(15)',
        'UpToDate' => 'Boolean',
        'Stable' => 'Boolean',
        'Git' => 'Text',
    );
}