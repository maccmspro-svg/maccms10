<?php
/**
 * 全站播放模式（仅配置文件，不读数据库）
 *
 * play_mode:
 *   - site     站内播放（默认）
 *   - external 全站强制站外：与后台「视频-播放器」里单独勾选「站外跳转」二选一或同时生效
 *
 * external_hint: 全站站外时的副标题说明；若仅某播放器站外，优先用后台该播放器「提示」字段，为空则用此处
 * external_btn_text: 站外模式下红色按钮文案（全站默认；各播放器可在后台单独填写覆盖）
 *
 * 后台每个播放器「播放模式」保存在 application/extra/vodplayer.php（play_mode=1 为站外）
 *
 * 站外播放链接仅在「播放页 / 内嵌 player」内生成；详情页「立即播放」仍进入本站播放页。
 */
return [
    'play_mode'          => 'site',
    'external_btn_text'  => '前往站外播放',
    'external_hint'      => '本站不嵌入视频播放。请从下方选择集数或线路，将直接前往站外播放页面。',
];
