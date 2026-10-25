<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\Validator;

class CardPrintExports
{
    public function qr(string $text): string
    {
        abort_unless(strlen($text) <= 256, 422);

        return (new Writer(new ImageRenderer(new RendererStyle(240, 4), new SvgImageBackEnd)))->writeString($text);
    }

    /** Render privately, then recheck authority and every printed identity before releasing bytes. */
    public function output(User $actor, array $input): string
    {
        $school = app(CurrentSchool::class)->id();
        $pdf = $this->pdf($actor, $input);
        $bytes = $pdf->output();
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        app(CardAccess::class)->manage($actor, false, false);
        foreach ($input['card_ids'] as $id) {
            app(CardAccess::class)->active(Card::findOrFail($id));
        }

        return $bytes;
    }

    public function pdf(User $actor, array $input): DomPdf
    {
        app(CardAccess::class)->manage($actor, false, false);
        $data = Validator::make($input, ['card_ids' => ['required', 'array', 'min:1', 'max:50'], 'card_ids.*' => ['required', 'uuid', 'distinct'],
            'layout' => ['required', 'in:landscape,portrait']])->validate();
        $cards = [];
        foreach ($data['card_ids'] as $id) {
            app(CardAccess::class)->manage($actor, false, false);
            $card = Card::findOrFail($id);
            $person = app(CardAccess::class)->active($card);
            $portrait = app(CardPortraits::class)->owner($actor, (int) $person->id, $card->type);
            $locator = 'NELOC1.'.app(CurrentSchool::class)->id().'.'.$card->id;
            $cards[] = ['name' => $person->full_name, 'type' => $card->type, 'school' => app(CurrentSchool::class)->school()->name,
                'photo' => 'data:image/jpeg;base64,'.base64_encode($portrait['bytes']),
                'qr' => 'data:image/svg+xml;base64,'.base64_encode($this->qr($locator)), 'id' => $card->id];
        }
        $portrait = $data['layout'] === 'portrait';
        $width = $portrait ? 54 : 85.6;
        $height = $portrait ? 85.6 : 54;

        return Pdf::loadView('cards.print', compact('cards', 'width', 'height', 'portrait'))
            ->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4])->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
    }
}
