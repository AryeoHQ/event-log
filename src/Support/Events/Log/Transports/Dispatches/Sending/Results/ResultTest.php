<?php

declare(strict_types=1);

namespace Support\Events\Log\Transports\Dispatches\Sending\Results;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Events\Log\DeliveryAttempts\DeliveryAttempt;
use Tests\TestCase;

#[CoversClass(Result::class)]
final class ResultTest extends TestCase
{
    #[Test]
    public function it_creates_with_a_message_and_code(): void
    {
        $result = Result::make('body', 200);

        $this->assertSame('body', $result->message);
        $this->assertSame(200, $result->code);
    }

    #[Test]
    public function it_creates_with_a_message_and_null_code(): void
    {
        $result = Result::make('published');

        $this->assertSame('published', $result->message);
        $this->assertNull($result->code);
    }

    #[Test]
    public function it_stringifies_with_code(): void
    {
        $this->assertSame('500: error', (string) Result::make('error', 500));
    }

    #[Test]
    public function it_stringifies_without_code(): void
    {
        $this->assertSame('published', (string) Result::make('published'));
    }

    #[Test]
    public function it_serializes_to_json(): void
    {
        $result = Result::make('body', 200);

        $this->assertSame(['code' => 200, 'message' => 'body'], $result->jsonSerialize());
    }

    #[Test]
    public function it_serializes_null_code_to_json(): void
    {
        $result = Result::make('published');

        $this->assertSame(['code' => null, 'message' => 'published'], $result->jsonSerialize());
    }

    #[Test]
    public function it_round_trips_through_the_cast_with_a_code(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->update(['result' => Result::make('body', 200)]);

        $attempt = $attempt->fresh();

        $this->assertInstanceOf(Result::class, $attempt->result);
        $this->assertSame('body', $attempt->result->message);
        $this->assertSame(200, $attempt->result->code);
    }

    #[Test]
    public function it_round_trips_through_the_cast_with_a_null_code(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->update(['result' => Result::make('published')]);

        $attempt = $attempt->fresh();

        $this->assertInstanceOf(Result::class, $attempt->result);
        $this->assertSame('published', $attempt->result->message);
        $this->assertNull($attempt->result->code);
    }

    #[Test]
    public function it_normalizes_a_bare_string_through_the_cast(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->update(['result' => 'raw string']);

        $attempt = $attempt->fresh();

        $this->assertInstanceOf(Result::class, $attempt->result);
        $this->assertSame('raw string', $attempt->result->message);
        $this->assertNull($attempt->result->code);
    }

    #[Test]
    public function it_rejects_a_bad_type_through_the_cast(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->update(['result' => 42]);
    }

    #[Test]
    public function it_does_not_overwrite_a_recorded_result(): void
    {
        $attempt = DeliveryAttempt::factory()->mqtt()->createQuietly();

        $attempt->update(['result' => Result::make('first', 200)]);
        $attempt->update(['result' => Result::make('second', 500)]);

        $attempt = $attempt->fresh();

        $this->assertSame('first', $attempt->result->message);
        $this->assertSame(200, $attempt->result->code);
    }

    #[Test]
    public function it_truncates_a_long_message(): void
    {
        config(['event_log.delivery_attempts.results.messages.length' => 10]);

        $result = Result::make(str_repeat('a', 100));

        $this->assertSame(13, strlen($result->message)); // 10 chars + '...'
        $this->assertTrue(str_ends_with($result->message, '...'));
    }
}
