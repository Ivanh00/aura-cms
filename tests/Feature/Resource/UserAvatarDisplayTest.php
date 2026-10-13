<?php

use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('users without an avatar display first and last name initials', function () {
    $user = new User(['name' => 'Demo Middle User']);

    $html = $user->displayFieldValue('avatar', null);

    expect($user->getInitials())->toBe('DU')
        ->and($html)->toContain('DU')
        ->and($html)->toContain('h-10 w-10')
        ->and($html)->toContain('rounded-full')
        ->and($html)->toContain('items-center')
        ->and($html)->toContain('justify-center');
});

test('a user with one available name displays one initial', function () {
    $user = new User(['name' => 'Prince']);

    expect($user->getInitials())->toBe('P')
        ->and($user->displayFieldValue('avatar', []))->toContain('P');
});

test('a user with no usable name still displays a non-blank fallback', function () {
    $user = new User(['name' => '   ']);

    expect($user->getInitials())->toBe('?')
        ->and($user->displayFieldValue('avatar', null))->toContain('?');
});

test('uploaded avatars display as images and removing one restores initials', function () {
    config(['aura.resources.attachment' => UserAvatarAttachment::class]);

    $attachment = UserAvatarAttachment::create([
        'name' => 'Avatar',
        'url' => 'avatar.png',
        'mime_type' => 'image/png',
        'size' => 1234,
    ]);

    $user = new User(['name' => 'Enes Emini']);

    expect($user->displayFieldValue('avatar', $attachment->id))
        ->toContain('USER-AVATAR/'.$attachment->id)
        ->toContain('object-cover object-center w-10 h-10 rounded-full')
        ->not->toContain('width: 6rem')
        ->not->toContain('>EE<')
        ->and($user->displayFieldValue('avatar', null))
        ->toContain('EE');
});

class UserAvatarAttachment extends Attachment
{
    public function thumbnail(?string $size = 'sm'): string
    {
        return 'USER-AVATAR/'.$this->id;
    }
}
