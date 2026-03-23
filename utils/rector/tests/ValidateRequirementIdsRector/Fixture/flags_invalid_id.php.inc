<?php

namespace ZeroToProd\Thryds;

use ZeroToProd\Framework\Attributes\Requirement;

#[Requirement('INVALID-999')]
class SomeClass
{
}

-----
<?php

namespace ZeroToProd\Thryds;

use ZeroToProd\Framework\Attributes\Requirement;

// TODO: [ValidateRequirementIdsRector] Requirement ID 'INVALID-999' not found in requirements.yaml. See: docs/requirement-tracing.md
#[Requirement('INVALID-999')]
class SomeClass
{
}
