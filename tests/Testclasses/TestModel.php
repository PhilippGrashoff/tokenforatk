<?php declare(strict_types=1);

namespace PhilippR\Atk4\Token\Tests\Testclasses;

use Atk4\Data\Model;
use PhilippR\Atk4\SecondaryModel\SecondaryModelRelationTrait;
use PhilippR\Atk4\Token\Token;

class TestModel extends Model
{
    use SecondaryModelRelationTrait;

    public $table = 'test_model';

    protected function init(): void
    {
      parent::init();
      $this->addField('name');
      $this->addSecondaryModelHasMany(Token::class);
    }
}