<?php

it('includes the login operation and reusable schemas in generated OpenAPI docs', function () {
    $this->artisan('l5-swagger:generate')->assertSuccessful();

    $spec = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($spec['paths']['/api/v1/login']['post'])
        ->operationId->toBe('authLogin')
        ->summary->toBe('Login user')
        ->tags->toBe(['Auth'])
        ->and($spec['paths']['/api/v1/login']['post']['requestBody']['content']['application/json']['schema']['$ref'])
        ->toBe('#/components/schemas/LoginRequest')
        ->and($spec['components']['schemas'])
        ->toHaveKeys(['LoginRequest', 'User', 'AuthTokenData']);
});
