<?php

namespace App\Http\Controllers;

use App\Services\PlayWallet\PlayWalletClient;
use App\Services\PlayWallet\PlayWalletException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlayWalletController extends Controller
{
    public function balance(PlayWalletClient $client): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $client->getBalance(),
        ]);
    }

    /**
     * Proxy for PlayWallet create-order endpoint.
     *
     * @throws ValidationException
     */
    public function createOrder(Request $request, PlayWalletClient $client): JsonResponse
    {
        $validated = $request->validate([
            'externalId' => ['required', 'string', 'max:255'],
            'serviceId' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'login' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => $client->createOrder(
                externalId: (string) $validated['externalId'],
                serviceId: (string) $validated['serviceId'],
                amount: number_format((float) $validated['amount'], 2, '.', ''),
                login: (string) $validated['login'],
            ),
        ]);
    }

    /**
     * Proxy for PlayWallet pay-order endpoint.
     *
     * @throws ValidationException
     */
    public function payOrder(Request $request, PlayWalletClient $client): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255'],
            'externalId' => ['required', 'string', 'max:255'],
            'createdDateTime' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => $client->payOrder(
                id: (string) $validated['id'],
                externalId: (string) $validated['externalId'],
                createdDateTime: (string) $validated['createdDateTime'],
            ),
        ]);
    }

    public function orderStatus(string $id, PlayWalletClient $client): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => $client->getOrder($id),
        ]);
    }

    public function orderList(Request $request, PlayWalletClient $client): JsonResponse
    {
        $offset = (int) $request->integer('offset', 0);
        $limit = (int) $request->integer('limit', 10);

        return response()->json([
            'ok' => true,
            'data' => $client->getOrderList($offset, $limit),
        ]);
    }
}

