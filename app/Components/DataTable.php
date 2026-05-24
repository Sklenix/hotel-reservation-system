<?php


namespace HotelSystem\Components;


use HotelSystem\Model\Repository\DataTableRepository;
use HotelSystem\Model\Repository\HotelRepository;
use HotelSystem\Model\Repository\RoomRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\SubmitButton;
use Nette\InvalidArgumentException;

class DataTable extends Control
{
    const
        RANGE_FILTER = 1,
        CHECKBOX_LIST_FILTER = 2,
        SELECT_BOX_FILTER = 3,
        TEXT_INPUT_FILTER = 4,
        INTEGER_INPUT_FILTER = 5;

    /** @var DataTableRepository */
    private $repository;

    /** @var int */
    private $itemsPerPage;

    /** @var array */
    private $filters;



    public function __construct(DataTableRepository $repository, array $filters = [], int $itemsPerPage = 50)
    {
        $this->repository = $repository;
        $this->itemsPerPage = $itemsPerPage;
        $this->filters = $filters;
    }


    /**
     * Vrátí pole výsledků pro vyrenderování
     * @param array $filtersToApply
     * @return array
     */
    private function getResultsArray(array $filtersToApply = []): array
    {
        $paginator = $this['visualPaginator']->getPaginator();
        return $this->repository->applyDataTableFilters($filtersToApply)
            ->setDataCollection($this->itemsPerPage, $paginator->getOffset())
            ->getDataTableArray();
    }



    public function render()
    {
        switch (TRUE) {
            case $this->repository instanceof HotelRepository:
                $this->template->title = 'Seznam hotelů';
                $this->template->destPresenter = 'Hotel';
                break;
            case $this->repository instanceof RoomRepository:
                $this->template->title = 'Seznam pokojů';
                $this->template->destPresenter = 'Room';
                break;
            default:
                $this->template->title = '';
                break;
        }

        $filtersToApply = [];
        foreach ($this->filters as $filter) {
            if (array_key_exists('defaultValue', $filter) && $filter['defaultValue'] !== NULL) {
                $filtersToApply[$filter['name']] = $filter['defaultValue'];
            }
        }

        $this->template->results = $this->getResultsArray($filtersToApply);
        $this->template->render(__DIR__ . '/DataTable.latte');
    }


    /**
     * Komponenta pro stránkování
     * @return VisualPaginator
     */
    public function createComponentVisualPaginator(): VisualPaginator
    {
        $visualPaginator = new VisualPaginator;
        $paginator = $visualPaginator->getPaginator();
        $paginator->setItemsPerPage($this->itemsPerPage);
        $paginator->setItemCount($this->repository->getTable()->count('*'));
        return $visualPaginator;
    }


    /**
     * Komponenta s filtry, při submitu překreslí tabulku
     * @return Form
     */
    public function createComponentFilters(): Form
    {
        $form = new Form;

        $form->addSubmit('cancel', 'Zrušit filtry')
            ->setHtmlAttribute('class', 'btn btn-danger btn-lg btn-block')
            ->setHtmlAttribute('style', 'margin-left:15px; margin-bottom: 10px;')
            ->onClick[] = function (SubmitButton $button) {
            $this->presenter->redirect('this');
        };

        foreach ($this->filters as $filterProperties) {
            switch ($filterProperties['type']) {
                case self::CHECKBOX_LIST_FILTER:
                    $filter = $form->addCheckboxList($filterProperties['name'], $filterProperties['label'], $filterProperties['items'])
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    break;
                case self::SELECT_BOX_FILTER:
                    $filter = $form->addSelect($filterProperties['name'], $filterProperties['label'], $filterProperties['items'])
                        ->setPrompt('- Vše -')
                        ->setHtmlAttribute('class', 'form-control form-control-lg')
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    break;
                case self::TEXT_INPUT_FILTER:
                    $filter = $form->addText($filterProperties['name'], $filterProperties['label'])
                        ->setHtmlAttribute('class', 'form-control form-control-lg')
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    break;
                case self::RANGE_FILTER:
                    $form->addText($filterProperties['name'] . '1', $filterProperties['label'] . ' od')
                        ->setHtmlAttribute('class', 'form-control form-control-lg')
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    $form->addText($filterProperties['name'] . '2', $filterProperties['label'] . ' do')
                        ->setHtmlAttribute('class', 'form-control form-control-lg')
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    break;
                case self::INTEGER_INPUT_FILTER:
                    $filter = $form->addInteger($filterProperties['name'], $filterProperties['label'])
                        ->setHtmlAttribute('class', 'form-control form-control-lg')
                        ->setHtmlAttribute('style', 'margin-bottom:15px;margin-left:15px;');
                    break;
                default:
                    throw new InvalidArgumentException('Unknown filter type');
            }
            if (isset($filterProperties['defaultValue']) && isset($filter)) {
                $filter->setDefaultValue($filterProperties['defaultValue']);
            }
        }

        $form->addSubmit('send', 'Filtrovat')
            ->setHtmlAttribute('class', 'btn btn-primary btn-lg btn-block')
            ->setHtmlAttribute('style', 'margin-left:15px;')
            ->onClick[] = function (SubmitButton $button) {
            $values = $button->getForm()->getValues(TRUE);
            $results = $this->getResultsArray(array_filter($values));

            $this['visualPaginator']->getPaginator()->setItemCount(count($results));
            $this->template->results = $results;
            $this->redrawControl();
        };

        return $form;
    }
}