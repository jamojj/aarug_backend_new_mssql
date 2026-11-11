<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HaisMail extends Mailable
{
    use Queueable, SerializesModels;
    public $subject;
    public $details;
    public $viewName;
    public $attachmentContent;
    public $attachmentName;
    public $attachmentMime;
    protected $ccRecipients;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subject, $details, $viewName,  $attachmentContent, $attachmentName = null, $attachmentMime = null, $ccRecipients = [])
    {
        $this->subject = $subject;
        $this->details = $details;
        $this->viewName = $viewName;
        $this->attachmentContent = $attachmentContent;
        $this->attachmentName = $attachmentName;
        $this->attachmentMime = $attachmentMime;
        $this->ccRecipients = $ccRecipients;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->subject($this->subject)
        ->view($this->viewName)
        ->with('details', $this->details);

    if ($this->attachmentContent) {
        $email->attachData($this->attachmentContent, $this->attachmentName ?? 'attachment.pdf', [
            'mime' => $this->attachmentMime ?? 'application/pdf',
        ]);
    }

    if (!empty($this->ccRecipients)) {
        $email->cc($this->ccRecipients);
    }

    return $email;
    }
}
