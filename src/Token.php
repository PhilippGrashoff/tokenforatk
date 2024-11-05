<?php declare(strict_types=1);

namespace PhilippR\Atk4\Token;

use Atk4\Data\Exception;
use Atk4\Data\Model;
use DateTime;
use PhilippR\Atk4\ModelTraits\CryptIdTrait;
use PhilippR\Atk4\SecondaryModel\SecondaryModel;

class Token extends SecondaryModel
{

    use CryptIdTrait;

    public $table = 'token';

    /**
     * @var int if this is set, on insert the expiry date is automatically set
     */
    public int $expiresAfterInMinutes = 0;

    /**
     * @var int how many chars are used for the token
     */
    public int $tokenLength = 64;


    protected function init(): void
    {
        parent::init();

        $this->addField('name');
        //an optional expiry date for the token
        $this->addField('expires', ['type' => 'datetime']);

        //in this field, the actual token is stored
        $this->addCryptIdFieldAndHooks('token');

        //set expiration on insert
        $this->onHook(
            Model::HOOK_BEFORE_SAVE,
            function (self $entity, bool $isUpdate) {
                if (!$isUpdate) {
                    $entity->setExpiresFromDefault();
                }
            }
        );

        //if token is expired do not load but throw exception
        $this->onHook(
            Model::HOOK_AFTER_LOAD,
            function (self $entity) {
                $entity->assertIsNotExpired();
            }
        );
    }

    /**
     * @throws \Atk4\Core\Exception
     * @throws Exception
     */
    protected function setExpiresFromDefault(): void
    {
        if (
            !$this->get('expires') //leave option to custom set expires
            && $this->expiresAfterInMinutes > 0
        ) {
            $this->set(
                'expires',
                (new DateTime())->modify('+' . $this->expiresAfterInMinutes . ' Minutes')
            );
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function assertIsNotExpired(): void
    {
        if (
            $this->get('expires') instanceof \DateTimeInterFace
            && $this->get('expires') < new DateTime()
        ) {
            throw new TokenException(
                'The token is expired, it expired at ' . $this->get('expires')->format(DATE_ATOM),
                403
            );
        }
    }

    /**
     * returns a long random token, $this->tokenLength long
     *
     * @return string
     * @throws \Exception
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