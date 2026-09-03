---
paths:
  - 'app/OpenApi/**'
  - 'app/Http/Controllers/**'
---

# OpenAPI documentation

Keep swagger-php metadata out of controllers. Controllers only attach a dedicated operation attribute (for example `#[Login]`) on the action method.

Put reusable pieces under `app/OpenApi/`:
- `Schemas/` for request bodies and payload objects (`#[OA\Schema]`)
- `Responses/` for custom `OA\Response` subclasses (`SuccessResponse`, `ErrorResponse`)
- `Operations/{Area}/` for custom `OA\Post` / `OA\Get` subclasses that compose those schemas and responses
- `app/OpenApi.php` for document-level Info, Server, Tag, and SecurityScheme

Do not inline large `#[OA\Post]` / `#[OA\Get]` trees on controller methods. Reference schema classes with `ref: SomeSchema::class` instead of repeating properties.
