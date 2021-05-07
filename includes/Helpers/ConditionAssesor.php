<?php

namespace FluentCrm\Includes\Helpers;

class ConditionAssesor
{
    public static function matchAllGroups($groups, $inputs, $matchType = 'match_any')
    {
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
            }
        }

        return false;
    }
}
