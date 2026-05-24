<?php


namespace HotelSystem\Model\Entity;


use Nette\Utils\Image;

/**
 * Trait pro entity s obrázky
 * Trait EntityImageTrait
 * @package HotelSystem\Model\Entity
 */
trait EntityImageTrait
{
    /** @var array */
    private $imagesToInsert = [];


    /**
     * Přidá cestu k obrázku k uložení do databáze
     * @param string $imagePath
     */
    public function addImage(string $imagePath): void
    {
        $this->imagesToInsert[] = $imagePath;
    }


    /**
     * Vrátí pole cest k obrázkům pro uložení do databáze
     * @return array
     */
    public function getImagesToInsert(): array
    {
        return $this->imagesToInsert;
    }


    /**
     * Funkce by měla vracet pole cest k obrázkům získané z databáze
     * @return array
     */
    protected abstract function getImagesPath(): ?array;


    /**
     * Vrací pole objektů Image, pole je vytvořeno z cest k obrázkům získaných pomocí funkce getImagesPath
     * @return array
     */
    public function getImages(): ?array
    {
        return array_map(function (string $imagePath) {
            return Image::fromFile($imagePath)->resize(350, 350);
        }, $this->getImagesPath());
    }
}