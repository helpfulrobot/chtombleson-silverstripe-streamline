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

    public function stableText()
    {
        return $this->Stable ? 'Yes' : 'No';
    }

    public function upToDateText()
    {
        return $this->UpToDate ? 'Yes' : 'No';
    }
}