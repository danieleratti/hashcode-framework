<?php

use Utils\ArrayUtils;

$fileName = 'e';

/** @var Contributor[] */
global $contributors;

/** @var Project[] */
global $projects;

/** @var Output */
global $OUTPUT;

/* Reader */
include_once 'reader.php';

ArrayUtils::array_keysort_objects($projects, '');

/** @var Project $project */
foreach ($projects as $project) {
    $selectedContributors = [];
    foreach ($project->roles as $role) {
        $selected = null;
        /** @var Contributor $contributor */
        foreach ($contributors as $contributor) {
            if ($contributor->skills[$role['skill']] >= $role['level'] && !isset($selectedContributors[$contributor->name])) {
                $selected = $contributor;
                break;
            }
        }

        if (!$selected)
            break;

        $selectedContributors[$selected->name] = $selected;
    }

    if (count($selectedContributors) < count($project->roles))
        continue;

    $OUTPUT->setProjectAndScore($project, $selectedContributors);
}

$OUTPUT->save();
