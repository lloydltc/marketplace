<?php

namespace App\Notifications;

use App\Modules\Inspection\Models\Inspection;
use Illuminate\Notifications\Messages\MailMessage;

/** TI4: notifies a buyer that their inspection report is ready. */
class InspectionReportReadyNotification extends ChannelAwareNotification
{
    public function __construct(public readonly Inspection $inspection) {}

    public function type(): string
    {
        return 'inspection';
    }

    public function payload(object $notifiable): array
    {
        return [
            'title' => 'Your inspection report is ready',
            'body'  => $this->inspection->vehicleLabel() . ' — verdict: ' . ($this->inspection->verdict ?? 'see report'),
            'url'   => route('inspections.show', $this->inspection),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your vehicle inspection report is ready')
            ->line($this->inspection->vehicleLabel() . ' — the inspector has submitted the report.')
            ->action('View report', route('inspections.show', $this->inspection));
    }
}
