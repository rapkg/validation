<?php
/**
 * User: coderd
 * Date: 2016/11/18
 * Time: 11:04
 */

namespace Rapkg\Validation;


interface MessageInterface
{
    /**
     * Retrieve rule-messages
     * Example:
     * [
     *      'rule-name' => 'message',
     * ]
     *
     * @return array
     */
    public function getRuleMessages();

    /**
     * Retrieve custom messages
     * Example:
     * [
     *      'attribute-name1' => [
     *          'rule-name1' => 'custom-message1',
     *      ],
     *      'attribute-name2' => 'custom-message2',
     * ]
     *
     * @return array
     */
    public function getCustomMessages();

    /**
     * Retrieve custom Validation Attributes
     *
     * The returned array is used to swap attribute place-holders
     * with something more reader friendly such as E-Mail Address instead
     * of "email". This simply helps us make messages a little cleaner.
     *
     * Example:
     * [
     *      'email' => 'E-Mail',
     * ]
     *
     * @return array
     */
    public function getAttributes();
}