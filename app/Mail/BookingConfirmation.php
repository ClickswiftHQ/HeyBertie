<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class BookingConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $manageUrl;

    public function __construct(
        public Booking $booking,
    ) {
        $this->manageUrl = URL::signedRoute('customer.bookings.show', [
            'ref' => $this->booking->booking_reference,
        ]);
    }

    public function envelope(): Envelope
    {
        $subject = $this->booking->status === 'confirmed'
            ? "Booking Confirmed — {$this->booking->booking_reference}"
            : "Booking Received — {$this->booking->booking_reference}";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.booking.confirmation',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
