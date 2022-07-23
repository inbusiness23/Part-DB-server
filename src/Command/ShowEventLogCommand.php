<?php
/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

/**
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 * Copyright (C) 2019 - 2020 Jan Böhmer (https://github.com/jbtronics)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 */

namespace App\Command;

use App\Entity\Base\AbstractNamedDBElement;
use App\Entity\LogSystem\AbstractLogEntry;
use App\Services\ElementTypeNameGenerator;
use App\Services\LogSystem\LogEntryExtraFormatter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShowEventLogCommand extends Command
{
    protected static $defaultName = 'app:show-logs';
    protected $entityManager;
    protected $translator;
    protected $elementTypeNameGenerator;
    protected $repo;
    protected $formatter;

    public function __construct(EntityManagerInterface $entityManager,
        TranslatorInterface $translator, ElementTypeNameGenerator $elementTypeNameGenerator, LogEntryExtraFormatter $formatter)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->elementTypeNameGenerator = $elementTypeNameGenerator;
        $this->formatter = $formatter;

        $this->repo = $this->entityManager->getRepository(AbstractLogEntry::class);
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $onePage = $input->getOption('onePage');

        $desc = (bool) $input->getOption('oldest_first');
        $limit = (int) $input->getOption('count');
        $page = (int) $input->getOption('page');
        $showExtra = $input->getOption('showExtra');

        $total_count = $this->repo->count([]);
        $max_page = (int) ceil($total_count / $limit);

        if ($page > $max_page && $max_page > 0) {
            $io->error("There is no page ${page}! The maximum page is ${max_page}.");

            return 1;
        }

        $io->note("There are a total of ${total_count} log entries in the DB.");

        $continue = true;
        while ($continue && $page <= $max_page) {
            $this->showPage($output, $desc, $limit, $page, $max_page, $showExtra);

            if ($onePage) {
                return 0;
            }

            $continue = $io->confirm('Do you want to show the next page?');
            ++$page;
        }

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('List the last event log entries.')
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'How many log entries should be shown per page.', 50)
            ->addOption('oldest_first', null, InputOption::VALUE_NONE, 'Show older entries first.')
            ->addOption('page', 'p', InputOption::VALUE_REQUIRED, 'Which page should be shown?', 1)
            ->addOption('onePage', null, InputOption::VALUE_NONE, 'Show only one page (dont ask to go to next).')
            ->addOption('showExtra', 'x', InputOption::VALUE_NONE, 'Show a column with the extra data.');
    }

    protected function showPage(OutputInterface $output, bool $desc, int $limit, int $page, int $max_page, bool $showExtra): void
    {
        $sorting = $desc ? 'ASC' : 'DESC';
        $offset = ($page - 1) * $limit;

        /** @var AbstractLogEntry[] $entries */
        $entries = $this->repo->getLogsOrderedByTimestamp($sorting, $limit, $offset);

        $table = new Table($output);
        $table->setHeaderTitle("Page ${page} / ${max_page}");
        $headers = ['ID', 'Timestamp', 'Type', 'User', 'Target Type', 'Target'];
        if ($showExtra) {
            $headers[] = 'Extra data';
            $table->setColumnMaxWidth(6, 50);
        }
        $table->setHeaders($headers);

        foreach ($entries as $entry) {
            $this->addTableRow($table, $entry, $showExtra);
        }

        $table->setColumnMaxWidth(3, 20);
        $table->setColumnMaxWidth(5, 30);

        $table->render();
    }

    protected function addTableRow(Table $table, AbstractLogEntry $entry, bool $showExtra): void
    {
        $target = $this->repo->getTargetElement($entry);
        $target_name = '';
        if ($target instanceof AbstractNamedDBElement) {
            $target_name = $target->getName().' <info>('.$target->getID().')</info>';
        } elseif ($entry->getTargetID()) {
            $target_name = '<info>('.$entry->getTargetID().')</info>';
        }

        $target_class = '';
        if (null !== $entry->getTargetClass()) {
            $target_class = $this->elementTypeNameGenerator->getLocalizedTypeLabel($entry->getTargetClass());
        }

        $row = [
            $entry->getID(),
            $entry->getTimestamp()->format('Y-m-d H:i:s'),
            $entry->getType(),
            $entry->getUser()->getFullName(true),
            $target_class,
            $target_name,
        ];

        if ($showExtra) {
            $row[] = $this->formatter->formatConsole($entry);
        }

        $table->addRow($row);
    }
}
