<?php

declare(strict_types=1);

use App\Domain\User\User;
use App\Domain\User\UserGender;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var User $user
 * @var array{surname: string, name: string, patronymic: string, company_name: string, description: string, phone: string, gender: string} $formValues
 * @var string|null $successMessage
 * @var string|null $errorMessage
 * @var string|null $csrf
 */

$this->setTitle('Личный кабинет — Botato');
?>

<section class="cabinet">
    <div class="container">
        <div class="cabinet__card">
            <div class="cabinet__head">
                <h1>Личный кабинет</h1>
                <button type="button" class="btn btn-ghost" id="logout-button">Выйти</button>
            </div>

            <?php if ($successMessage !== null): ?>
                <div class="form-success"><?= Html::encode($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage !== null): ?>
                <div class="form-error"><?= Html::encode($errorMessage) ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <?php if ($csrf !== null): ?>
                    <input type="hidden" name="_csrf" value="<?= Html::encode($csrf) ?>">
                <?php endif; ?>
                <div class="form-field">
                    <label>Email</label>
                    <input type="email" value="<?= Html::encode($user->getEmail()) ?>" readonly>
                </div>

                <div class="form-field-row">
                    <div class="form-field">
                        <label for="surname">Фамилия</label>
                        <input id="surname" type="text" name="surname" required maxlength="255" value="<?= Html::encode($formValues['surname']) ?>">
                    </div>

                    <div class="form-field">
                        <label for="name">Имя</label>
                        <input id="name" type="text" name="name" required maxlength="255" value="<?= Html::encode($formValues['name']) ?>">
                    </div>

                    <div class="form-field">
                        <label for="patronymic">Отчество</label>
                        <input id="patronymic" type="text" name="patronymic" maxlength="255" value="<?= Html::encode($formValues['patronymic']) ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label for="gender">Пол</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled <?= $formValues['gender'] === '' ? 'selected' : '' ?>>Выберите пол</option>
                        <option value="<?= UserGender::Male->value ?>" <?= $formValues['gender'] === UserGender::Male->value ? 'selected' : '' ?>>
                            <?= Html::encode(UserGender::Male->label()) ?>
                        </option>
                        <option value="<?= UserGender::Female->value ?>" <?= $formValues['gender'] === UserGender::Female->value ? 'selected' : '' ?>>
                            <?= Html::encode(UserGender::Female->label()) ?>
                        </option>
                    </select>
                    <p class="form-field__hint">ИИ будет отвечать клиентам от вашего имени с учётом рода.</p>
                </div>

                <div class="form-field">
                    <label for="company_name">Название компании / бизнеса</label>
                    <input id="company_name" type="text" name="company_name" required maxlength="255" value="<?= Html::encode($formValues['company_name']) ?>">
                </div>

                <div class="form-field">
                    <label for="description">Описание бизнеса для контекста ИИ</label>
                    <textarea id="description" name="description" required maxlength="2000" placeholder="Опишите услуги, аудиторию, tone of voice и особенности вашего бизнеса."><?= Html::encode($formValues['description']) ?></textarea>
                </div>

                <div class="form-field">
                    <label for="phone">Телефон</label>
                    <input id="phone" type="tel" name="phone" data-phone-mask value="<?= Html::encode($formValues['phone']) ?>" placeholder="+7 (___) ___-__-__">
                </div>

                <button type="submit" class="btn btn-primary">Сохранить профиль</button>
            </form>
        </div>
    </div>
</section>

<script>
document.getElementById('logout-button')?.addEventListener('click', async () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const response = await fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
    });
    const data = await response.json();
    window.location.href = data.redirect || '/';
});
</script>
