<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'email',
    'phone',
    'subject',
    'interest',
    'message',
    'ip_address',
    'user_agent',
    'is_read',
    'is_archived',
])]
class Message extends Model
{
    /** @use HasFactory<\Database\Factories\MessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function scopeInbox(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function getInitialsAttribute(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * Prefilled reply body for the "Reply" action. The admin inbox uses a
     * mailto: link rather than stored replies, because sending from the
     * server requires SMTP that is not yet configured.
     */
    public function replyMailto(): string
    {
        $subject = $this->subject
            ? 'Re: '.$this->subject
            : 'Re: your enquiry to Region of Huronia Environmental Services';

        $body = "\n\n--- Original message ---\n"
            ."From: {$this->name}\n"
            ."Sent: {$this->created_at?->format('j F Y, g:i a')}\n\n"
            ."{$this->message}\n";

        return 'mailto:'.$this->email
            .'?subject='.rawurlencode($subject)
            .'&body='.rawurlencode($body);
    }
}
