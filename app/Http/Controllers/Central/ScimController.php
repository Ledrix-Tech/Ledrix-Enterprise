<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Security\ScimProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ScimController extends Controller
{
    public function __construct(
        private ScimProvisioningService $scim,
    ) {}

    public function serviceProviderConfig(): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:ServiceProviderConfig'],
            'patch'   => ['supported' => true],
            'bulk'    => ['supported' => false],
            'filter'  => ['supported' => false],
            'changePassword' => ['supported' => false],
            'sort'    => ['supported' => false],
            'etag'    => ['supported' => false],
            'authenticationSchemes' => [[
                'type'        => 'oauthbearertoken',
                'name'        => 'Bearer Token',
                'description' => 'SCIM bearer token configured in SCIM_BEARER_TOKEN',
            ]],
        ]);
    }

    public function listUsers(Request $request): JsonResponse
    {
        $startIndex = max(1, (int) $request->query('startIndex', 1));
        $count = min(200, max(1, (int) $request->query('count', 100)));

        return response()->json($this->scim->listUsers($startIndex, $count));
    }

    public function showUser(string $id): JsonResponse
    {
        $user = $this->scim->findUser($id);

        if (! $user) {
            return $this->notFound('User not found.');
        }

        return response()->json($user);
    }

    public function createUser(Request $request): JsonResponse
    {
        try {
            $user = $this->scim->createUser($request->all());
        } catch (RuntimeException $e) {
            return response()->json([
                'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
                'status'  => '400',
                'detail'  => $e->getMessage(),
            ], 400);
        }

        return response()->json($user, 201);
    }

    public function patchUser(Request $request, string $id): JsonResponse
    {
        $operations = $request->input('Operations', []);
        $payload = [];

        foreach ($operations as $operation) {
            if (($operation['op'] ?? '') === 'replace') {
                $payload = array_merge($payload, (array) ($operation['value'] ?? []));
            }
        }

        if ($payload === [] && $request->has('active')) {
            $payload = $request->only(['active', 'displayName']);
        }

        try {
            $user = $this->scim->updateUser($id, $payload);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('User not found.');
        }

        if ($user === null) {
            return response()->json(null, 204);
        }

        return response()->json($user);
    }

    public function deleteUser(string $id): JsonResponse
    {
        try {
            $this->scim->deactivateUser($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('User not found.');
        }

        return response()->json(null, 204);
    }

    private function notFound(string $detail): JsonResponse
    {
        return response()->json([
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:Error'],
            'status'  => '404',
            'detail'  => $detail,
        ], 404);
    }
}
