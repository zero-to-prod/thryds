<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class Repository
{
    public function persist(object $entity): void {}
}

class AddVoidNoReturn
{
    private Repository $repository;

    public function save(object $entity)
    {
        $this->repository->persist($entity);
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class Repository
{
    public function persist(object $entity): void {}
}

class AddVoidNoReturn
{
    private Repository $repository;

    public function save(object $entity): void
    {
        $this->repository->persist($entity);
    }
}

?>
