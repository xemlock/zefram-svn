<?php

class Zefram_Controller_Action_Helper_Json extends Zend_Controller_Action_Helper_Json
{
    public function encodeJson($data, $keepLayouts = false, $encodeData = true)
    {
        if ($encodeData) {
            // Do not auto-escape forward shashes, as it helps when embedding
            // JSON in a <script> tag, which doesn't allow </ inside strings.
            // For safeness reason Zefram_Json::encode is used instead of
            // Zend_Json counterpart, as the latter does not handle UTF-8
            // separators specially.
            $encodeData = false;
            $data = Zefram_Json::encode($data);
        }

        return parent::encodeJson($data, $keepLayouts, $encodeData);
    }
}
