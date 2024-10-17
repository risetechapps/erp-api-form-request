<?php

namespace RiseTech\FormRequest;

use \RiseTech\FormRequest\Models\FormRequest as FormRequestModel;

class ValidationRuleRepository
{

    public function getRules(string $name, array $parameter = []): array
    {

        $validationRules = [
            'rules'=> [],
            'messages' => []
        ];

        $model = new FormRequestModel();

        $where = array_merge([
            'form' => $name
        ], $parameter);

        $results = $model
            ->where($where)
            ->first(['rules']);

        if (!empty($results)) {
            $validationRules = $results->toArray();
            $validationRules['messages'] = $this->generateMessages($validationRules['rules']);
        } else {

            $default = Rules::defaultRules();

            if (array_key_exists($name, $default)) {
                $validationRules['rules'] = $default[$name];
                $validationRules['messages'] = $this->generateMessages($validationRules['rules']);
            }
        }

        if (array_key_exists('id', $parameter)) {
            $validationRules['rules'] = $this->setIdUpdate($parameter['id'], $validationRules['rules']);
        }
        return $validationRules;
    }

    protected function generateMessages(array $rules): array
    {
        $messages = [];
        foreach ($rules as $key => $value) {
            $messages = array_merge($messages,
                $this->extractRules($key, $value));
        }

        return $messages;
    }

    protected function extractRules($field, $rulesString): array
    {
        $formattedRules = [];

        foreach (explode('|', $rulesString) as $rule) {
            $formattedRules[$field . '.' . trim(explode(':', $rule)[0])] = $field . '.' . trim(explode(':', $rule)[0]);
        }

        return $formattedRules;

    }

    private function setIdUpdate(mixed $id, $rules): array
    {
        return array_map(function ($rule) use ($id) {
            if (str_contains($rule, 'unique:')) {
                $parts = explode('|', $rule);
                foreach ($parts as &$part) {
                    if (str_contains($part, 'unique:')) {
                        $part .= ',' . $id;
                    }
                }
                return implode('|', $parts);
            }
            return $rule;
        }, $rules);
    }
}
