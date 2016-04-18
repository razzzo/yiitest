<?php
/**
 * Plik klasy Api.
 * @copyright Copyright (c) 2015 Razzo Piotr Mróz
 */

namespace common\components;

use yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Api aplikacji dające dostęp i obsługuje wybrane komponenty aplikacji.
 * Dostarcza funkcjonalności:
 * - wysyłanie e-mail,
 * - dostęp do komponentu config.
 *
 * @author Piotr Mróz <mroz.piotrek@gmail.com>
 */
class Api extends Component
{
    /**
     * Wysyła wiadomość e-mail.
     * Jako parametr wejściowy przyjmuje tablicę konfigurującą wysłanie wiadomości.
     * Opis tablicy parametrów:
     * - `htmlLayout`: layout HTML wiadomości, wartość domyślna 'layouts/html'
     * - `view`: plik widoku wiadomości
     * - `viewParams`: tablica parametrów, które zostaną przekazane do widoku
     * - `to`: e-mail odbiorcy
     * - `replyTo`: e-mail w polu 'Odpowiedz do'
     * - `replyToName`: nazwa w polu 'Odpowiedz do'
     * - `subject`: temat wiadomości
     *
     * Poniższe 2 parametry są nieobsługiwane przez metodę:
     * - `textBody`: gdy nie podano pliku widoku należy samodzielnie podać treść e-maila
     * - `htmlBody`: j.w.
     *
     * Przykład użycia metody:
     * ~~
     * Yii::$app->api->mail([
     *        'to' => 'mroz.piotrek@gmail.com',
     *        'view' =>'test',
     *        'viewParams' => ['user' => 'Piotrek'],
     *        'subject' => 'Temat wiadomości',
     *        'replyTo' => 'pm58@wp.pl',
     *        'replyToName' => 'Obsługa klienta'
     * ]);
     * ~~
     * @param array $params parametry metody.
     * @return boolean wartość informująca czy metoda wykonała się prawidłowo.
     * @throws InvalidConfigException
     */
    public function mail($params = [])
    {
        if (!isset($params['view']) || !isset($params['to'])) {
            throw new InvalidConfigException('Parametry "view" - nazwa widoku wiadomości i "to" - odbiorca, muszą być podane.');
        }
        if (!isset($params['replyTo'])) {
            $params['replyTo'] = $this->config('mail', 'replyToEmail');
        }
        if (!isset($params['replyToName'])) {
            $params['replyToName'] = $this->config('mail', 'replyToName');
        }

        $params = $this->getRecipient($params);
        $this->configMailComponent();
        restore_exception_handler();
        try {
            return Yii::$app->mailer->compose(['html' => $params['view'] . '-html', 'text' => $params['view'] . '-text'], $params['viewParams'])
                ->setTo($params['to'])
                ->setFrom([$this->config('mail', 'fromEmail') => $this->config('mail', 'fromName')])
                ->setReplyTo([$params['replyTo'] => $params['replyToName']])
                ->setSubject($params['subject'])
                ->send();

        } catch (\Exception $e) {
            $error = [
                'errorName' => $e->getName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
            Yii::getLogger()->log([
                'message' => 'Błąd podczas wysyłania wiadomości e-mail.',
                'data' => serialize($error),
            ], \yii\log\Logger::LEVEL_ERROR, 'app:' . self::className() . ':email');

            return $error;
        }
    }

    /**
     * Daje możliwość odczytu i zapisu z/do konfiguracji aplikacji.
     * Dodatkowo, jeżeli parametr $key jest null, metoda zwróci całą kategorię
     * konfiguracji w postaci tablicy.
     *
     * @param string $category kategoria do której należy klucz
     * @param string $key klucz którego wartość pobierana jest z konfiguracji
     * @param mixed $newValue nowa wartość klucza (jest zmieniana gdy wartość podana)
     * @return mixed zwraca Config dla set i wartość klucza $key dla get
     */
    public function config($category, $key = null, $newValue = null)
    {
        if ($key == null) {
            return $this->configCategory($category);
        }
        if (isset($newValue)) {
            return Yii::$app->config->set($category, $key, $newValue);
        }
        return Yii::$app->config->get($category, $key);
    }

    /**
     * Zwraca wszystkie klucze konfiguracji danej kategorii.
     *
     * @param string $category nazwa kategorii
     * @return array wszystkie klucze danej kategorii
     */
    public function configCategory($category)
    {
        return Yii::$app->config->load($category);
    }

    /**
     * Czyści cache dla komponentu konfiguracji aplikacji.
     * Po wyczyszczeniu każdy następny odczyt zmiennej zostanie wykonany z
     * bazy danych.
     *
     * @param string $category kategoria dla której zostanie wyczyszczony cache.
     * Gdy nie podano zostanie wyczyszczony cały cache konfiguracji.
     * @return CmsSettings
     */
    public function configDeleteCache($category = '')
    {
        return Yii::$app->config->deleteCache($category);
    }

    /**
     * Gdy aplikacja działa w środowisku `dev` metoda sprawdza czy w parametrach
     * aplikacji został zdefiniowany parametr `mailerDeveloperEmail`.
     * Jeżeli tak i parametr jest prawidłowym adresem e-mail, to jako adresat każdej wysłanej
     * wiadomości zostanie ustawiony e-mail programisty a do tematu wiadomości
     * zostanie dodany tekst: [właściwy odbiorca: email@odbiorcy.tu].
     *
     * @param array $params parametry przekazane metodzie mail()
     * @return array odpowiednio zmodyfikowane parametry wcześniej przekazane metodzie mail()
     * @throws InvalidConfigException
     */
    protected function getRecipient($params)
    {
        if (YII_ENV === 'dev' && isset(Yii::$app->params['mailerDeveloperEmail'])) {
            $developerEmail = Yii::$app->params['mailerDeveloperEmail'];
            $emailValidator = new yii\validators\EmailValidator();
            if ($developerEmail !== false && $emailValidator->validate($developerEmail)) {
                $to = is_array($params['to']) ? 'wielu odbiorców (' . count($params['to']) . ')' : $params['to'];
                $params['subject'] = '[właściwy odbiorca: ' . $to . '] ' . $params['subject'];
                $params['to'] = $developerEmail;
                return $params;
            } elseif ($developerEmail === false) {
                return $params;
            } else {
                throw new InvalidConfigException('Parametr aplikacji `mailerDeveloperEmail` musi być poprawnym adresem e-mail, być wartością false lub nie istnieć w parametrach aplikacji.');
            }
        }

        return $params;
    }

    /**
     * Konfiguruje komponent `mail` aplikacji wczytując dane konta SMTP służącego
     * do wysyłki wiadomości z komponentu `config` aplikacji.
     */
    protected function configMailComponent()
    {
        Yii::$app->mailer->setTransport([
            'class' => 'Swift_SmtpTransport',
            'host' => $this->config('mail', 'smtpHost'),
            'port' => $this->config('mail', 'smtpPort'),
            'username' => $this->config('mail', 'username'),
            'password' => $this->config('mail', 'password'),
            //'encryption' => 'tls',
        ]);
    }
}
