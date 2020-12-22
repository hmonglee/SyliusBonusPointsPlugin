<?php

declare(strict_types=1);

namespace Tests\BitBag\SyliusBonusPointsPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use BitBag\SyliusBonusPointsPlugin\Entity\BonusPointsStrategyInterface;
use BitBag\SyliusBonusPointsPlugin\Entity\BonusPointsStrategyRuleInterface;
use BitBag\SyliusBonusPointsPlugin\Repository\BonusPointsStrategyRepositoryInterface;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class BonusPointsStrategyContext implements Context
{
    /** @var SharedStorageInterface */
    private $sharedStorage;

    /** @var ObjectManager */
    private $objectManager;

    /** @var FactoryInterface */
    private $bonusPointsStrategyFactory;

    /** @var FactoryInterface */
    private $bonusPointsStrategyRuleFactory;

    /** @var BonusPointsStrategyRepositoryInterface */
    private $bonusPointsStrategyRepository;

    /** @var TaxonRepositoryInterface */
    private $taxonRepository;

    public function __construct(
        FactoryInterface $bonusPointsStrategyFactory,
        FactoryInterface $bonusPointsStrategyRuleFactory,
        SharedStorageInterface $sharedStorage,
        ObjectManager $objectManager,
        BonusPointsStrategyRepositoryInterface $bonusPointsStrategyRepository,
        TaxonRepositoryInterface $taxonRepository
    ) {
        $this->sharedStorage = $sharedStorage;
        $this->objectManager = $objectManager;
        $this->bonusPointsStrategyFactory = $bonusPointsStrategyFactory;
        $this->bonusPointsStrategyRuleFactory = $bonusPointsStrategyRuleFactory;
        $this->bonusPointsStrategyRepository = $bonusPointsStrategyRepository;
        $this->taxonRepository = $taxonRepository;
    }

    /**
     * @Given there is bonus points strategy with code :code and name :name with rule :ruleType with :taxon taxon
     */
    public function thereIsABonusPointsStrategyWithRuleWithTaxon(
        string $code,
        string $name,
        string $ruleType,
        string $taxon
    ): void {
        $bonusPointsStrategy = $this->createBonusPointsStrategy($code, $name);

        $this->createBonusPointsStrategyRule($bonusPointsStrategy, $ruleType, $taxon);

        $this->objectManager->persist($bonusPointsStrategy);
        $this->objectManager->flush();
    }

    /**
     * @Given the bonus points strategy :code admits :points points per one currency
     */
    public function theAdmitsPointsPerOneCurrency(string $code, string $points)
    {
        /** @var BonusPointsStrategyInterface $bonusPointsStrategy */
        $bonusPointsStrategy = $this->bonusPointsStrategyRepository->findOneBy(['code' => $code]);

        $bonusPointsStrategy->setCalculatorConfiguration(['numberOfPointsEarnedPerOneCurrency' => $points]);

        $this->objectManager->persist($bonusPointsStrategy);
        $this->objectManager->flush();
    }

    private function createBonusPointsStrategy(string $code, string $name): BonusPointsStrategyInterface
    {
        /** @var BonusPointsStrategyInterface $bonusPointsStrategy */
        $bonusPointsStrategy = $this->bonusPointsStrategyFactory->createNew();

        $bonusPointsStrategy->setCode($code);
        $bonusPointsStrategy->setName($name);
        $bonusPointsStrategy->enable();
        $bonusPointsStrategy->setCalculatorType('per_order_price');

        return $bonusPointsStrategy;
    }

    private function createBonusPointsStrategyRule(BonusPointsStrategyInterface $bonusPointsStrategy, string $ruleType, string $taxonName)
    {
        $ruleType = str_replace(' ', '_', strtolower($ruleType));
        $configurationTaxons = [];
        $taxons = $this->taxonRepository->findBy(['code' => $taxonName]);

        /** @var TaxonInterface $taxon */
        foreach ($taxons as $taxon) {
            $configurationTaxons[] = $taxon->getCode();
        }

        /** @var BonusPointsStrategyRuleInterface $rule */
        $rule = $this->bonusPointsStrategyRuleFactory->createNew();
        $rule->setType($ruleType);
        $rule->setConfiguration(['taxons' => $configurationTaxons]);
        $bonusPointsStrategy->addRule($rule);

        $this->objectManager->persist($rule);
    }
}
