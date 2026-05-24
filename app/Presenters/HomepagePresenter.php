<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;


class HomepagePresenter extends BasePresenter
{
    public function renderDefault()
    {
        $this->template->rooms = $this->roomRepository->getRandomRooms();
    }
}
