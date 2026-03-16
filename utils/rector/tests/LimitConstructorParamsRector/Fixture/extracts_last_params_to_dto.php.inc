<?php

namespace Fixture;

class OrderProcessor
{
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly TaxCalculator $TaxCalculator,
        private readonly NotificationService $NotificationService,
        private readonly AuditLogger $AuditLogger,
    ) {}

    public function process(): void
    {
        $this->OrderRepository->save();
        $this->NotificationService->send();
    }
}

?>
-----
<?php

namespace Fixture;

class OrderProcessor
{
    public function __construct(
        private readonly OrderRepository $OrderRepository,
        private readonly PaymentGateway $PaymentGateway,
        private readonly ShippingService $ShippingService,
        private readonly \Fixture\OrderProcessorDeps $OrderProcessorDeps,
    ) {}

    public function process(): void
    {
        $this->OrderRepository->save();
        $this->OrderProcessorDeps->NotificationService->send();
    }
}

?>
