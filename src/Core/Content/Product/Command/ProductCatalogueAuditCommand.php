<?php declare(strict_types=1);

namespace Shopware\Core\Content\Product\Command;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Reports products whose catalogue data gaps degrade SEO, structured data and
 * product feed (Google Merchant Center) quality, so merchants can fix the data
 * at the source instead of hunting for it manually.
 *
 * @internal
 */
#[AsCommand(
    name: 'product:catalogue:audit',
    description: 'Reports products with catalogue data gaps that hurt SEO, structured data and product feeds',
)]
#[Package('inventory')]
class ProductCatalogueAuditCommand extends Command
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(private readonly EntityRepository $productRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Number of affected product numbers to list per check (0 to hide samples)', '20');
        $this->addOption('parents-only', null, InputOption::VALUE_NONE, 'Only audit parent and standalone products (skip variants)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createCLIContext();
        $limit = max(0, (int) $input->getOption('limit'));
        $parentsOnly = (bool) $input->getOption('parents-only');

        /**
         * Each check maps a human readable label to the filter that selects the
         * products *missing* that piece of data.
         */
        $checks = [
            'Missing GTIN/EAN' => new EqualsFilter('ean', null),
            'Missing brand (manufacturer)' => new EqualsFilter('manufacturerId', null),
            'Missing meta description' => new EqualsFilter('metaDescription', null),
            'Missing cover image' => new EqualsFilter('coverId', null),
        ];

        $total = $this->count($context, null, $parentsOnly);
        if ($total === 0) {
            $io->warning('No products found.');

            return self::SUCCESS;
        }

        $io->title('Product catalogue audit');
        $io->text(\sprintf(
            'Auditing %d product%s%s',
            $total,
            $total === 1 ? '' : 's',
            $parentsOnly ? ' (parent and standalone products only)' : ''
        ));

        $rows = [];
        foreach ($checks as $label => $filter) {
            $affected = $this->count($context, $filter, $parentsOnly);
            $rows[] = [$label, $affected, \sprintf('%.1f %%', $affected / $total * 100)];
        }
        $io->table(['Check', 'Affected', 'Share'], $rows);

        if ($limit > 0) {
            foreach ($checks as $label => $filter) {
                $numbers = $this->sample($context, $filter, $parentsOnly, $limit);
                if ($numbers === []) {
                    continue;
                }

                $io->section($label);
                $io->listing($numbers);
            }
        }

        return self::SUCCESS;
    }

    private function baseCriteria(bool $parentsOnly): Criteria
    {
        $criteria = new Criteria();
        if ($parentsOnly) {
            $criteria->addFilter(new EqualsFilter('parentId', null));
        }

        return $criteria;
    }

    private function count(Context $context, ?Filter $filter, bool $parentsOnly): int
    {
        $criteria = $this->baseCriteria($parentsOnly);
        $criteria->setLimit(1);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        if ($filter !== null) {
            $criteria->addFilter($filter);
        }

        return $this->productRepository->search($criteria, $context)->getTotal();
    }

    /**
     * @return array<string>
     */
    private function sample(Context $context, Filter $filter, bool $parentsOnly, int $limit): array
    {
        $criteria = $this->baseCriteria($parentsOnly);
        $criteria->addFilter($filter);
        $criteria->setLimit($limit);
        $criteria->addSorting(new FieldSorting('productNumber'));

        $products = $this->productRepository->search($criteria, $context)->getEntities();

        $numbers = [];
        foreach ($products as $product) {
            $numbers[] = $product->getProductNumber();
        }

        return $numbers;
    }
}
