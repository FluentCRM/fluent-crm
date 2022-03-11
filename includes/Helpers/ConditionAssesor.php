<?php

namespace FluentCrm\Includes\Helpers;

use FluentCrm\App\Services\Libs\ConditionAssessor;

/**
 * @deprecated No longer used by internal code and not recommended. Please use FluentCrm\App\Services\Libs\ConditionAssessor instead
 */

class ConditionAssesor
{
    public static function matchAllGroups($groups, $inputs, $matchType = 'match_any')
    {
        _doing_it_wrong(__FUNCTION__, 'Use FluentCrm\App\Services\Libs\ConditionAssessor::matchAllGroups() instead', '2.5.0');

        $hasConditionMet = true;
        foreach ($groups as $group) {
            $hasConditionMet = self::evaluate($group, $inputs);
            if($hasConditionMet && $matchType == 'match_any') {
                return true;
            }
            if ($matchType === 'match_all' && !$hasConditionMet) {
                return false;
            }
        }

        return $hasConditionMet;
    }

    public static function evaluate($conditionGroup, $inputs)
    {
        _doing_it_wrong(__FUNCTION__, 'Use FluentCrm\App\Services\Libs\ConditionAssessor::evaluate() instead', '2.5.0');

        $hasConditionMet = true;
        $conditionals =  Arr::get($conditionGroup, 'conditions');

        if ($conditionals) {
            $toMatch = Arr::get($conditionGroup, 'match_type');
            foreach ($conditionals as $conditional) {
                $hasConditionMet = static::assess($conditional, $inputs);

                if($hasConditionMet && $toMatch == 'match_any') {
                    return true;
                }
                if ($toMatch === 'match_all' && !$hasConditionMet) {
                    return false;
                }
            }
        }

        return $hasConditionMet;
    }

    public static function assess($conditional, $inputs)
    {
        _doing_it_wrong(__FUNCTION__, 'Use FluentCrm\App\Services\Libs\ConditionAssessor::assess() instead', '2.5.0');

        if ($conditional['data_key']) {
            $sourceValue = Arr::get($inputs, $conditional['data_key']);
            $dataValue = $conditional['data_value'];

            switch ($conditional['operator']) {
                case '=':
                    if(is_array($sourceValue)) {
                        return in_array($dataValue, $sourceValue);
                    }
                    return $sourceValue == $dataValue;
                    break;
                case '!=':
                    if(is_array($sourceValue)) {
                        return !in_array($dataValue, $sourceValue);
                    }
                    return $sourceValue != $dataValue;
                    break;
                case '>':
                    return $sourceValue > $dataValue;
                    break;
                case '<':
                    return $sourceValue < $dataValue;
                    break;
                case '>=':
                    return $sourceValue >= $dataValue;
                    break;
                case '<=':
                    return $sourceValue <= $dataValue;
                    break;
                case 'startsWith':
                    return Str::startsWith($sourceValue, $dataValue);
                    break;
                case 'endsWith':
                    return Str::endsWith($sourceValue, $dataValue);
                    break;
                case 'contains':
                    return Str::contains($sourceValue, $dataValue);
                    break;
                case 'doNotContains':
                    return !Str::contains($sourceValue, $dataValue);
                    break;
                case 'length_equal':
                    if(is_array($sourceValue)) {
                        return count($sourceValue) == $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) == $dataValue;
                    break;
                case 'length_less_than':
                    if(is_array($sourceValue)) {
                        return count($sourceValue) < $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) < $dataValue;
                    break;
                case 'length_greater_than':
                    if(is_array($sourceValue)) {
                        return count($sourceValue) > $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) > $dataValue;
                    break;
                case 'match_all':
                    $sourceValue = (array) $sourceValue;
                    $dataValue = (array) $dataValue;
                    sort($sourceValue);
                    sort($dataValue);
                    return $sourceValue == $dataValue;
                    break;
                case 'match_none_of':
                    $sourceValue = (array) $sourceValue;
                    $dataValue = (array) $dataValue;
                    return !(array_intersect($sourceValue, $dataValue));
                    break;
            }
        }

        return false;
    }
}
