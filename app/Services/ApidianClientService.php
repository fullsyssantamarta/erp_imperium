<?php

namespace App\Services;

use App\Exceptions\ApidianRequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ApidianClientService
{
    /**
     * @var ApidianEndpointResolverService
     */
    private $endpointResolver;

    /**
     * @var Client
     */
    private $httpClient;

    public function __construct(ApidianEndpointResolverService $endpointResolver, Client $httpClient = null)
    {
        $this->endpointResolver = $endpointResolver;
        $this->httpClient = $httpClient ?: new Client();
    }

    /**
     * Send a document payload to APIDIAN and return the parsed response metadata.
     *
     * @param  array  $payload  Complete payload expected by APIDIAN.
     * @param  array  $options  base_url, token, environment (production|test), test_id, timeout, verify.
     * @throws ApidianRequestException
     */
    public function sendDocument(array $payload, array $options = []): array
    {
    $baseUrl = $this->resolveBaseUrl($options);
    $token = $options['token'] ?? config('tenant.api_token_service_fact');
        $environment = $options['environment'] ?? 'production';
        $testId = $options['test_id'] ?? null;
        $timeout = $options['timeout'] ?? 60;
        $verify = $options['verify'] ?? false; // default to false to mimic legacy cURL flags

        if (empty($token)) {
            throw new ApidianRequestException('El token de autenticación para APIDIAN no está configurado.');
        }

        $endpointInfo = $this->endpointResolver->resolveEndpoint(
            $payload,
            $baseUrl,
            $environment,
            $testId
        );

        $requestContext = [
            'endpoint' => $endpointInfo['endpoint'] ?? null,
            'full_url' => $endpointInfo['full_url'] ?? null,
            'environment' => $environment,
            'test_id' => $testId,
        ];

        try {
            $response = $this->httpClient->request('POST', $endpointInfo['full_url'], [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ],
                'json' => $payload,
                'timeout' => $timeout,
                'verify' => $verify,
                'http_errors' => false,
            ]);
        } catch (RequestException $exception) {
            Log::error('APIDIAN request failed', $requestContext + ['exception' => $exception->getMessage()]);

            if ($exception->hasResponse()) {
                $response = $exception->getResponse();
                $message = $this->buildErrorMessage($response, $requestContext);

                throw new ApidianRequestException($message, $requestContext + ['status' => $response->getStatusCode()], 0, $exception);
            }

            throw new ApidianRequestException(
                'No fue posible comunicarse con el servicio APIDIAN: '.$exception->getMessage(),
                $requestContext,
                0,
                $exception
            );
        } catch (Throwable $exception) {
            Log::error('APIDIAN request failed', $requestContext + ['exception' => $exception->getMessage()]);

            throw new ApidianRequestException(
                'No fue posible comunicarse con el servicio APIDIAN: '.$exception->getMessage(),
                $requestContext,
                0,
                $exception
            );
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            $message = $this->buildErrorMessage($response, $requestContext);
            Log::warning('APIDIAN request returned failure response', $requestContext + [
                'status' => $status,
                'body' => $body,
            ]);

            throw new ApidianRequestException($message, $requestContext + ['status' => $status]);
        }

        Log::info('APIDIAN request succeeded', $requestContext + [
            'status' => $status,
        ]);

        return [
            'status' => $status,
            'endpoint' => $endpointInfo,
            'data' => $decoded,
            'raw_body' => $body,
            'response' => $response,
        ];
    }

    private function resolveBaseUrl(array $options): string
    {
        $baseUrl = $options['base_url'] ?? config('tenant.service_fact');

        if (empty($baseUrl)) {
            throw new ApidianRequestException('La URL base del servicio APIDIAN no está configurada.');
        }

        return rtrim($baseUrl, '/').'/';
    }

    private function buildErrorMessage(ResponseInterface $response, array $context): string
    {
        $status = $response->getStatusCode();
        $bodyString = (string) $response->getBody();
        $body = json_decode($bodyString, true);

        if (is_array($body)) {
            $message = isset($body['message']) ? $body['message'] : (isset($body['Message']) ? $body['Message'] : null);
            $detailed = isset($body['errors']) ? $body['errors'] : (isset($body['ErrorMessage']) ? $body['ErrorMessage'] : null);

            if ($message) {
                return $message.(is_array($detailed) ? ' '.json_encode($detailed) : ' '.(string) $detailed);
            }
        }

        return sprintf(
            'El servicio APIDIAN respondió con estado %s. URL: %s. Detalle: %s',
            $status,
            isset($context['full_url']) ? $context['full_url'] : 'N/D',
            $bodyString
        );
    }
}
