<?php declare(strict_types=1);

namespace tokenforatk;

use Atk4\Data\Model;
use secondarymodelforatk\SecondaryModel;
use traitsforatkdata\CryptIdTrait;
use traitsforatkdata\UserException;


class Token extends SecondaryModel
{

    use CryptIdTrait;

    public $table = 'token';

    //if this is set, on insert the expiry date is automatically set
    public $expiresAfterInMinutes = 0;

    //how many chars are used for the token
    public $tokenLength = 64;


    protected function init(): void
    {
        parent::init();

        $this->addField(
            'expires',
            [
                'type' => 'datetime'
            ]
        );

        //before insert, create token string
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $model, $isUpdate) {
                if (!$model->get('value')) {
                    $model->setCryptId('value');
                }
                //set expiration on insert
                if (
                    !$isUpdate
                    && !$model->get('expires')
                    && $model->expiresAfterInMinutes > 0
                ) {
                    $model->set(
                        'expires',
                        (new \DateTime())->modify('+' . $model->expiresAfterInMinutes . ' Minutes')
                    );
                }
            }
        );

        //if token is expired do not load but throw exception
        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function (self $model) {
                if (
                    $model->get('expires') instanceof \DateTimeInterFace
                    && $model->get('expires') < new \DateTime()
                ) {
                    throw new UserException('Das Token ist abgelaufen.');
                }
            }
        );
    }

    /**
     * returns a long random token, $this->tokenLength long
     */
    protected function generateCryptId(): string
    {
        $return = '';
        for ($i = 0; $i < $this->tokenLength; $i++) {
            $return .= $this->getRandomChar();
        }

        return $return;
    }
}