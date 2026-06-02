<?php

namespace Tests\Unit;

use App\Services\ClusteringService;
use App\Services\MLServiceClient;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests untuk validasi fitur whitelist di ClusteringService.
 * MLServiceClient di-mock agar test tidak butuh network/DB.
 */
class ClusteringServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invalid_numeric_feature_is_rejected(): void
    {
        $ml = Mockery::mock(MLServiceClient::class);
        $svc = new ClusteringService($ml);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Fitur numerik tidak dikenal/');

        // Memanggil method privat via reflection
        $ref = new \ReflectionMethod($svc, 'validateFeatures');
        $ref->setAccessible(true);
        $ref->invoke($svc, ['kolom_tidak_ada'], []);
    }

    public function test_invalid_categorical_feature_is_rejected(): void
    {
        $ml = Mockery::mock(MLServiceClient::class);
        $svc = new ClusteringService($ml);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Fitur kategorikal tidak dikenal/');

        $ref = new \ReflectionMethod($svc, 'validateFeatures');
        $ref->setAccessible(true);
        $ref->invoke($svc, [], ['kolom_misterius']);
    }

    public function test_empty_feature_set_is_rejected(): void
    {
        $ml = Mockery::mock(MLServiceClient::class);
        $svc = new ClusteringService($ml);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Minimal satu fitur/');

        $ref = new \ReflectionMethod($svc, 'validateFeatures');
        $ref->setAccessible(true);
        $ref->invoke($svc, [], []);
    }

    public function test_valid_feature_set_passes(): void
    {
        $ml = Mockery::mock(MLServiceClient::class);
        $svc = new ClusteringService($ml);

        $ref = new \ReflectionMethod($svc, 'validateFeatures');
        $ref->setAccessible(true);
        // Tidak melempar exception
        $ref->invoke($svc, ['estimasi_kerugian'], ['jenis_kejahatan']);
        $this->assertTrue(true);
    }
}
