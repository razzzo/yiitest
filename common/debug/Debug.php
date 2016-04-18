<?php
/**
 * @link http://www.razzo.pl/
 * @copyright Copyright (c) 2015 Razzo
 */

namespace common\debug;

use Yii;

/**
 * Klasa umożliwia debugowanie za pomocą logu umieszczonego w Yii Debugger
 * oraz wyświetla debugi w konsoli Firebug.
 *
 * @author Piotr Mróz <mroz.piotrek@gmail.com>
 */
class Debug
{
    public function __construct($var, $label = null, $level = \yii\log\Logger::LEVEL_ERROR)
    {
        if (!is_null($var)) {
            if ($label == 'print_r') {
                self::debugPrintR($var, $level !== false);
            } else {
                self::debug($var, $label, $level);
            }
        } else {
            self::debug('Debug var is empty.', $label, $level);
        }
    }

    public static function debug($var, $label = null, $level = \yii\log\Logger::LEVEL_ERROR)
    {
        $var = self::prepareData($var);

        if (Yii::$app->request->isAjax) {
            $firephp = FirePHP::getInstance(true);
            // $firephp->log($var, $label);
            $firephp->info($var, $label);
            // $firephp->warn($var, $label);
            // $firephp->error($var, $label);
        } else {
            Yii::getLogger()->log(\yii\helpers\VarDumper::dumpAsString($var), $level);
            Yii::$app->controller->view->registerJs('console.warn("'.self::quote(\yii\helpers\VarDumper::dumpAsString($var)).'");');
        }
    }

    public static function debugPrintR($var, $end = true)
    {
        $var = self::prepareData($var);

        echo "<pre>";
        print_r($var);
        echo "</pre>";

        if ($end == true) {
            Yii::$app->end();
        }

    }

    private static function prepareData($var)
    {
        if ($var instanceof \yii\db\ActiveRecord || $var instanceof \yii\base\Model) {
            $var = self::ar2array($var);
        } elseif (is_array($var)) {
            foreach ($var as $k => $record) {
                if ($record instanceof \yii\db\ActiveRecord || $record instanceof \yii\base\Model) {
                    $rows[$k] = self::ar2array($record);
                } else {
                    $rows[$k] = $record;
                }

                $var = $rows;
            }
        }

        // przechodzi przez wszystkie komórki tablicy i sprawdza czy któraś zawiera wartość liczbową
        // zapisaną w notacji naukowej (scientific notation / exponential), np.: -1.5120349416975E-11 == -0.000000000015120349416975
        // jeżeli taka liczba zostanie wykryta, to za pomocą formatera jest konwertowana do postaci decimal z 15 miejscami po przecinku
        if (is_array($var)) {
            array_walk_recursive($var, function (&$item, $key) {
                if (is_float($item) && stripos($item, 'e') !== false) {
                    $item = Yii::$app->formatter->asDecimal($item, 15);
                }
            });
        }

        return $var;
    }

    /**
     * Konwertuje ActiveRecord lub Model do tablicy z rekurencyjnym uwzględnieniem
     * relacji o ile zostały one wczytane.
     *
     * @param  type  $aR ActiveRecord
     * @return array przekonwertowany ActiveRecord lub Model do tablicy
     */
    private static function ar2array($aR)
    {
        // jeżeli zostanie zastosowany toArray() to zwracana tablica nie będzie miała
        // kolumn których wartości to NULL
        //$array = $aR->toArray();

        // dzięki array_merge i Yii::getObjectVars($aR), do zwracanej tablicy zostaną również dołączone
        // wirtualne atrybuty modelu, które normalnie nie są zwracane przez getAttributes (chyba, że po nadpisaniu w
        // klasie ActiveRecordu metody attributes()
        $array = array_merge($aR->getAttributes(), Yii::getObjectVars($aR));

        if (method_exists($aR, 'getRelatedRecords')) {
            $related = $aR->getRelatedRecords();
            foreach ($related as $key => $record) {
                if (is_array($record) && count($record) > 0) {
                    foreach ($record as $k => $v) {
                        $array[$key][] = self::ar2array($v);
                    }
                } elseif ($record instanceof \yii\db\ActiveRecord || $record instanceof \yii\base\Model) {
                    $array[$key] = self::ar2array($record);
                } else {
                    $array[$key] = [];
                }
            }
        }

        return $array;
    }

    /**
     * Yii 1.1; helpers\CJavaScript
     * Quotes a javascript string.
     * After processing, the string can be safely enclosed within a pair of
     * quotation marks and serve as a javascript string.
     * @param  string  $js     string to be quoted
     * @param  boolean $forUrl whether this string is used as a URL
     * @return string  the quoted string
     */
    public static function quote($js, $forUrl = false)
    {
        if ($forUrl) {
            return strtr($js, ['%' => '%25', "\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/']);
        } else {
            return strtr($js, ["\t" => '\t', "\n" => '\n', "\r" => '\r', '"' => '\"', '\'' => '\\\'', '\\' => '\\\\', '</' => '<\/']);
        }
    }
}
