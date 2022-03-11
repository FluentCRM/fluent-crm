<?php

namespace FluentCrm\App\Services\Libs;

use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Support\Str;

class ConditionAssessor
{
    public static function matchAllGroups($groups, $inputs, $matchType = 'match_any')
    {
        $hasConditionMet = true;
        foreach ($groups as $group) {
            $hasConditionMet = self::evaluate($group, $inputs);
            if ($hasConditionMet && $matchType == 'match_any') {
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
        $conditionals = Arr::get($conditionGroup, 'conditions', []);

        if ($conditionals) {
            $toMatch = Arr::get($conditionGroup, 'match_type');
            foreach ($conditionals as $conditional) {
                $hasConditionMet = static::assess($conditional, $inputs);

                if ($hasConditionMet && $toMatch == 'match_any') {
                    return true;
                }
                if ($toMatch === 'match_all' && !$hasConditionMet) {
                    return false;
                }
            }
        }

        return $hasConditionMet;
    }

    public static function matchAllConditions($conditions, $inputs)
    {
        foreach ($conditions as $condition) {
            if (!static::assess($condition, $inputs)) {
                return false;
            }
        }

        return true;
    }

    public static function assess($conditional, $inputs)
    {
        if ($conditional['data_key']) {
            $sourceValue = Arr::get($inputs, $conditional['data_key']);
            $dataValue = $conditional['data_value'];

            switch ($conditional['operator']) {
                case '=':
                    if (is_array($sourceValue)) {
                        return in_array($dataValue, $sourceValue);
                    }
                    return $sourceValue == $dataValue;
                    break;
                case '!=':
                    if (is_array($sourceValue)) {
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

                    $sourceValue = strtolower($sourceValue);
                    if (is_string($dataValue)) {
                        $dataValue = strtolower($dataValue);
                    }

                    return Str::contains($sourceValue, $dataValue);
                    break;
                case 'doNotContains':
                case 'not_contains':
                    $sourceValue = strtolower($sourceValue);
                    if (is_string($dataValue)) {
                        $dataValue = strtolower($dataValue);
                    }
                    return !Str::contains($sourceValue, $dataValue);
                    break;
                case 'length_equal':
                    if (is_array($sourceValue)) {
                        return count($sourceValue) == $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) == $dataValue;
                    break;
                case 'length_less_than':
                    if (is_array($sourceValue)) {
                        return count($sourceValue) < $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) < $dataValue;
                    break;
                case 'length_greater_than':
                    if (is_array($sourceValue)) {
                        return count($sourceValue) > $dataValue;
                    }
                    $sourceValue = strval($sourceValue);
                    return strlen($sourceValue) > $dataValue;
                    break;
                case 'match_all':
                case 'in_all':
                    $sourceValue = (array)$sourceValue;
                    $dataValue = (array)$dataValue;
                    sort($sourceValue);
                    sort($dataValue);
                    return $sourceValue == $dataValue;
                    break;
                case 'match_none_of':
                case 'not_in_all':
                    $sourceValue = (array)$sourceValue;
                    $dataValue = (array)$dataValue;
                    return !(array_intersect($sourceValue, $dataValue));
                    break;
                case 'in':
                    $dataValue = (array)$dataValue;
                    if (is_array($sourceValue)) {
                        return !!(array_intersect($sourceValue, $dataValue));
                    }
                    return in_array($sourceValue, $dataValue);
                case 'not_in':
                    $dataValue = (array)$dataValue;
                    if (is_array($sourceValue)) {
                        return !(array_intersect($sourceValue, $dataValue));
                    }
                    return !in_array($sourceValue, $dataValue);
                case 'before':
                    return strtotime($sourceValue) < strtotime($dataValue);
                case 'after':
                    return strtotime($sourceValue) > strtotime($dataValue);
                case 'date_equal':
                    return date('YMD', strtotime($sourceValue)) == date('YMD', strtotime($dataValue));
                case 'days_before':
                    return strtotime($sourceValue) < strtotime("-{$dataValue} days", current_time('timestamp'));
                case 'days_within':
                    return strtotime($sourceValue) > strtotime("-{$dataValue} days", current_time('timestamp'));
            }
        }

        return false;
    }
}
