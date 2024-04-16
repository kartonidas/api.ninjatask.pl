<?php

namespace App\Libraries;

class Render
{
    private $content;
    private $variables;
    private $document;
    public function __construct($content, $varsArray = [])
    {
        $this->content = $content;
        $this->variables = $varsArray;
    }

    public function render($html = true)
    {
        if($html && !empty($this->variables) && $this->isArrayData())
            $this->content = self::prepareTables();

        $this->mapVariables();

        return $this->content;
    }

    private function prepareTables()
    {
        $arrayVariables = $this->getArrayData();

        $this->document = new \DOMDocument("1.0", "UTF-8");
        $this->document->loadHTML(mb_convert_encoding($this->content, "HTML-ENTITIES", "UTF-8"));
        $tables = $this->document->getElementsByTagName("table");
        foreach($tables as $table)
        {
            $dataSource = null;
            $generateTable = false;
            $rows = $table->getElementsByTagName("tr");
            foreach($rows as $row)
            {
                $cells = $row->getElementsByTagName("td");
                foreach($cells as $cell)
                {
                    if(preg_match("/\[TAB\.(" . implode("|", array_keys($arrayVariables)) . ")\.(.*)\]/i", $cell->textContent, $matches))
                    {
                        $dataSource = $matches[1] ?? "";
                        $generateTable = true;
                        break 2;
                    }
                }
            }

            if($generateTable)
            {
                $this->generateTable($table, $row, $dataSource);

                if(mb_strtolower($table->childNodes[0]->tagName) == "tbody")
                    $table->childNodes[0]->removeChild($row);
                else
                    $table->removeChild($row);
            }
        }

        return preg_replace("~<(?:!DOCTYPE|/?(?:html|body))[^>]*>\s*~i", "", $this->document->saveHTML());
    }

    private function generateTable($table, $row, $dataSource)
    {
        $rowIteration = 1;
        if(!empty($this->variables[$dataSource]))
        {
            foreach($this->variables[$dataSource] as $variables)
            {
                $data = [];
                foreach($variables as $field => $value)
                    $data[$dataSource . "." . $field] = $value;

                $newRow = clone $row;
                $cells = $newRow->getElementsByTagName("td");
                foreach($cells as $cellIndex => $cell)
                {
                    if(preg_match_all("/\[TAB\.(" . implode("|", array_keys($data)) . ")\]/i", $cell->textContent, $matches))
                    {
                        foreach($matches[0] as $i => $match)
                            $this->tryReplace($cell, $matches[0][$i], $data[$matches[1][$i]]);
                    }
                    $this->tryReplace($cell, "[TAB._index]", $rowIteration);
                }
                if(mb_strtolower($table->childNodes[0]->tagName) == "tbody")
                    $table->childNodes[0]->insertBefore($newRow, $row);
                else
                    $table->insertBefore($newRow, $row);

                $rowIteration++;
            }
        }
        else
        {
            // Nie ma wymaganych danych do tabeli - Wstawienie pustego wiersza (na ten moment nie używane)
            return;

            $newRow = clone $row;
            $colspan = count($newRow->getElementsByTagName("td"));
            while($newRow->hasChildNodes())
                $newRow->removeChild($newRow->firstChild);

            $TD = $this->createElement("TD", ["colspan" => $colspan]);
            $TD = $this->document->importNode($TD, true);
            $newRow->appendChild($TD);

            if(mb_strtolower($table->childNodes[0]->tagName) == "tbody")
                $table->childNodes[0]->appendChild($newRow);
            else
                $table->appendChild($newRow);
        }
    }

    private function tryReplace($node, $variable, $value)
    {
        if(!$node->hasChildNodes())
        {
            $node->textContent = str_replace($variable, $value, $node->textContent);
        }
        else
        {
            $nodes = $node->childNodes;
            foreach($nodes as $node)
                $this->tryReplace($node, $variable, $value);
        }
    }

    private function createElement($type, $attributes = [])
    {
        $doc = new \DOMDocument("1.0", "UTF-8");
        $newItem = $doc->createElement($type);

        if(!empty($attributes))
        {
            foreach($attributes as $attribute => $attributeValue)
                $newItem->setAttribute($attribute, $attributeValue);
        }
        return $newItem;
    }

    private function isArrayData()
    {
        foreach($this->variables as $var)
        {
            if(is_array($var))
                return true;
        }
        return false;
    }

    private function getArrayData()
    {
        $out = [];
        foreach($this->variables as $key => $variables)
        {
            if(is_array($variables))
            {
                if(empty($variables))
                    $out[$key] = array();
                else
                    foreach($variables as $field => $variable)
                        $out[$key][] = $variable;
            }
        }
        return $out;
    }

    private function getFlatData()
    {
        $out = [];
        foreach($this->variables as $field => $variable)
        {
            if(is_array($variable))
                continue;

            $out[$field] = $variable;
        }
        return $out;
    }

    private function mapVariables()
    {
        foreach($this->getFlatData() as $field => $value)
            $this->content = str_ireplace("[" . $field . "]", $value, $this->content);
    }
}
