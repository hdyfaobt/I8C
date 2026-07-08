<?php

it('redirects guests to the login page', function () {
    $response = $this->get('/');

    $response->assertStatus(302)->assertRedirect(route('login'));
});
