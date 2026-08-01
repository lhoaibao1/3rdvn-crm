<?php

use Illuminate\Support\Facades\Broadcast;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Helpers\MorphClassResolver;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chats.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Wirechat::conversationModelClass()::find($conversationId);

    return $conversation && $user->belongsToConversation($conversation);
}, [
    'guards' => ['web'],
]);

Broadcast::channel('chats.participant.{encodedType}.{id}', function ($user, $encodedType, $id) {
    try {
        $morphType = MorphClassResolver::decode($encodedType);
    } catch (Throwable) {
        return false;
    }

    return (int) $user->id === (int) $id
        && hash_equals($user->getMorphClass(), $morphType);
}, [
    'guards' => ['web'],
]);
