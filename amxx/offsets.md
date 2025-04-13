# ////////////////AMXX Offsets (Актуализированный)///////////////////////
```inc
////////////////AMXX Offsets (Актуализированный)///////////////////////

/*
  Этот файл содержит определения оффсетов для доступа к приватным переменным игровых сущностей.
  Многие оффсеты рассчитаны для движка Half-Life/Counter-Strike.
  
  ВАЖНО:
  - Для Linux-сборок некоторые значения требуют поправок (например, прибавление +4, +5 и т.д.).
  - Если обнаруживаете ошибки доступа (через get_pdata_int, get_pdata_float, get_pdata_ent),
    перепроверьте актуальность этих оффсетов относительно версии движка.
  - Регулярно сверяйте их с официальной документацией или исходниками.
*/

//------------------------------
// Предметы игрока (CBasePlayer)
// Используется для доступа к первому элементу массива предметов игрока.
stock const m_rgpPlayerItems_slot0 = 367;

//------------------------------
// Цепочка: CBaseEntity -> CBaseDelay -> CBaseAnimating
// (Linux: добавляется +4 к базовому указателю)
stock const XO_CBASEANIMATING = 4;

// Анимационные поля (CBaseAnimating)
stock const m_flFrameRate       = 36; // (float) Частота анимации.
stock const m_flGroundSpeed     = 37; // (float) Скорость объекта по земле.
stock const m_flLastEventCheck  = 38; // (float) Время последней проверки события.
stock const m_fSequenceFinished = 39; // (int) Флаг завершения анимационной последовательности.
stock const m_fSequenceLoops    = 40; // (int) Флаг или счётчик цикличности анимации.

//------------------------------
// Цепочка: CBaseEntity -> CBaseDelay
// (Linux: +4)
stock const XO_CBASEDELAY = 4;

// Поля, связанные с задержкой выполнения (CBaseDelay)
stock const m_flDelay       = 34; // (float) Задержка выполнения команды.
stock const m_iszKillTarget = 35; // (индекс строки) Имя цели для команды "убить".

//------------------------------
// Общие поля CBaseEntity
// Здесь задаются базовые указатели: виртуальная таблица, адрес структуры pev и ссылки.
stock const vtbl      = 0; // (указатель) Виртуальная таблица.
stock const _pev      = 1; // (указатель) Адрес структуры pev.
stock const m_pGoalEnt = 2; // (указатель) Ссылка на целевую сущность.
stock const m_pLink    = 3; // (указатель) Ссылка для связывания сущностей.

// Функциональные указатели, которые задают динамическое поведение объекта:
stock const m_pfnThink              = 4;  // Указатель на функцию Think.
stock const m_pfnThink_Flag_LINUX   = 5;  // Linux-специфичный флаг для Think.
stock const m_pfnTouch              = 5;  // Указатель на функцию Touch (на Linux возможно смещение +1).
stock const m_pfnTouch_Flag_LINUX   = 7;  // Linux-версия функции Touch.
stock const m_pfnUse                = 6;  // Указатель на функцию Use.
stock const m_pfnUse_Flag_LINUX     = 9;  // Linux-версия функции Use.
stock const m_pfnBlocked            = 7;  // Указатель на функцию Blocked.
stock const m_pfnBlocked_Flag_LINUX = 11; // Linux-версия функции Blocked.

//------------------------------
// Linux Diff 4 – Таблица боеприпасов
// Оффсеты для различных типов патронов (доступ через get_pdata_int)
stock const ammo_buckshot_TAB    = 11; // Buckshot (дробовик)
stock const ammo_9mm_TAB         = 13; // 9mm патроны
stock const ammo_556nato_TAB     = 15; // 5.56 Nato
stock const ammo_556natobox_TAB  = 17; // Коробка с 5.56 Nato
stock const ammo_762nato_TAB     = 19; // 7.62 Nato
stock const ammo_45acp_TAB       = 21; // 45 ACP
stock const ammo_50ae_TAB        = 23; // 50 AE
stock const ammo_338magnum_TAB   = 25; // 338 Magnum
stock const ammo_57mm_TAB        = 27; // 57mm патроны
stock const ammo_357sig_TAB      = 29; // 357 SIG

// Дополнительные поля для бросаемых объектов (например, гранат)
stock const m_flStartThrow    = 30; // (float) Время начала броска.
stock const m_flReleaseThrow  = 31; // (float) Время отпускания оружия или объекта.
stock const m_chargeReady     = 32; // (int, bool) Флаг готовности заряда.
stock const m_fInAttack       = 33; // (int, bool) Флаг, показывающий состояние атаки.

//------------------------------
// Цепочка: CBaseEntity -> CArmoury (Linux: +4)
// Для управления инвентаризацией оружия/предметов.
stock const XO_CARMOURY = 4;
stock const m_iItem         = 34;  // (int) Идентификатор оружия, заданный в FGD.
stock const m_iCount        = 35;  // (int) Количество оставшихся предметов.
stock const m_iSaveCount    = 36;  // (int) Сохранённое количество предметов при спавне.
stock const m_bRestoreCount = 148; // (int, bool) Флаг восстановления количества предметов.

//------------------------------
// Цепочка: CBaseEntity -> CPointEntity -> CMapInfo
// Параметры, связанные с информацией о карте.
stock const XO_CMAPINFO = 4;
stock const m_ibuying      = 34; // (int) Флаг, связанный с покупкой оружия и предметов.
stock const m_flBombRadius = 35; // (float) Радиус взрыва бомбы.

//------------------------------
// Цепочка: CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster
// (Linux: +5) — используются для монстров (NPC)
stock const XO_CBASEMONSTER = 5;
stock const m_Activity       = 73;  // (int) Текущая активность или анимация монстра.
stock const m_IdealActivity  = 74;  // (int) Идеальная активность монстра.
stock const m_LastHitGroup   = 75;  // (int) Группа попадания (направление урона).
stock const m_bitsDamageType = 76;  // (int) Битовая маска типов урона.

// Пример работы с time-based damage:
// Массив индексов, используемый для доступа к time-based повреждениям.
enum {
    itbd_Paralyze,     // Паралич
    itbd_NerveGas,     // Нервный газ
    itbd_Poison,       // Отравление
    itbd_Radiation,    // Радиация
    itbd_DrownRecover, // Восстановление после утопления
    itbd_Acid,         // Кислотный урон
    itbd_SlowBurn,     // Медленное горение
    itbd_SlowFreeze,   // Медленное замерзание
    CDMG_TIMEBASED     // Общее число типов урона
}
stock const m_rgbTimeBasedDamage[CDMG_TIMEBASED] = {308,309,...};
// Пример доступа:
//   new dmg = get_pdata_int(id, m_rgbTimeBasedDamage[itbd_DrownRecover]);
//   set_pdata_int(id, m_rgbTimeBasedDamage[itbd_Poison], 200);

//////////////////////////////////////////////////////////////////////////
// Дополнительные поля монстров
stock const m_MonsterState      = 79; // (int) Текущее состояние монстра.
stock const m_IdealMonsterState = 80; // (int) Идеальное состояние монстра.
stock const m_afConditions      = 81; // (int) Текущие флаги условий.
stock const m_afMemory          = 82; // (int) "Память" монстра – сохранённые флаги.
stock const m_flNextAttack      = 83; // (float) Время следующей атаки.

// Поля для отслеживания врагов и цели
stock const m_hEnemy_pent                = 336; // (указатель) Идентификатор врага (на Linux смещение может быть +20).
stock const m_hEnemy_serialnumber        = 85;  // (int) Серийный номер врага.
stock const m_hTargetEnt_pent            = 344; // (указатель) Идентификатор целевой сущности (Linux: +20).
stock const m_hTargetEnt_serialnumber    = 87;  // (int) Серийный номер целевой сущности.

// Дополнительные параметры
stock const m_flFieldOfView = 88;          // (float) Поле зрения монстра.
stock const m_bloodColor    = 89;          // (int) Цвет крови.
stock const m_HackedGunPos[3] = {90,91,92}; // (vector) Скорректированная позиция оружия.
stock const m_vecEnemyLKP[3]  = {93,94,95}; // (vector) Последняя известная позиция врага.

//////////////////////////////////////////////////////////////////////////
// Цепочка: CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem
// (Linux: +4) — поля для предметов, используемых игроком.
stock const XO_CBASEPLAYERITEM = 4;
stock const m_pPlayer = 41;   // (указатель) Указатель на игрока-владельца.
stock const m_pNext   = 42;     // (указатель) Указатель на следующий предмет.
stock const m_iId     = 43;     // (int) Идентификатор предмета.

//////////////////////////////////////////////////////////////////////////
// Цепочка: CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem -> CBasePlayerWeapon
// (Linux: +4) — поля для оружия игрока.
stock const XO_CBASEPLAYERWEAPON = 4;
stock const m_iPlayEmptySound        = 44;  // (int) Флаг проигрывания звука при пустом магазине.
stock const m_fFireOnEmpty           = 45;  // (int) Флаг стрельбы при отсутствии патронов.
stock const m_flNextPrimaryAttack    = 46;  // (float) Время следующего основного выстрела.
stock const m_flNextSecondaryAttack  = 47;  // (float) Время следующего вторичного выстрела.
stock const m_flTimeWeaponIdle       = 48;  // (float) Время перехода оружия в режим покоя.
stock const m_iPrimaryAmmoType       = 49;  // (int) Индекс основного типа патронов.
stock const m_iSecondaryAmmoType     = 50;  // (int) Индекс вторичного типа патронов.
stock const m_iClip                  = 51;  // (int) Количество патронов в магазине (-1, если не используется).
stock const m_iClientClip            = 52;  // (int) Последнее значение магазина у клиента.
stock const m_iClientWeaponState     = 53;  // (int) Состояние оружия для HUD.
stock const m_fInReload              = 54;  // (int) Флаг, что оружие находится в перезарядке.
stock const m_fInSpecialReload       = 55;  // (int) Флаг спецперезарядки (например, для дробовика).
stock const m_iDefaultAmmo           = 56;  // (int) Количество патронов при спавне оружия.
stock const m_iShellLate             = 57;  // (int) Задержка обработки оболочек.
stock const m_flWeaponSpeed          = 58;  // (float) Скорость работы оружия.
stock const m_bDelayFire             = 236; // (int) Флаг задержки стрельбы.

stock const m_iDirection             = 60;  // (int) Направление отдачи (1 = вправо, 0 = влево).
stock const m_flAccuracy             = 62;  // (float) Коэффициент точности оружия.
stock const m_flLastFire             = 63;  // (float) Время последнего выстрела.
stock const m_iShotFired            = 64;  // (int) Счётчик выстрелов.

stock const m_iszViewModel           = 68;  // (string) Идентификатор модели вида оружия.
stock const m_flGlock18Shoot         = 69;  // (float) Интервал стрельбы для Glock18.
stock const m_iGlock18ShotsFired     = 70;  // (int) Счётчик выстрелов Glock18.
stock const m_flFamasShoot           = 71;  // (float) Интервал стрельбы для Famas.
stock const m_iFamasShotsFired       = 72;  // (int) Счётчик выстрелов Famas.
stock const m_flFamasBurstSpread     = 73;  // (float) Разброс очереди для Famas.
stock const m_fWeaponState           = 74;  // (int) Текущее состояние оружия.

// Флаги состояния оружия (с кратными степенями 2)
enum ( <<=1 )
{
    WEAPONSTATE_USP_SILENCED = 1,    // USP с глушителем.
    WEAPONSTATE_GLOCK18_BURST_MODE,  // Режим очередной стрельбы Glock18.
    WEAPONSTATE_M4A1_SILENCED,       // M4A1 с глушителем.
    WEAPONSTATE_ELITE_LEFT,          // Dual Elite в левой руке.
    WEAPONSTATE_FAMAS_BURST_MODE,    // Режим очередной стрельбы Famas.
    WEAPONSTATE_SHIELD_DRAWN         // Флаг активированного щита.
};

stock const m_flNextReload           = 75;  // (float) Время следующей перезарядки.
stock const m_flDecreaseShotsFired   = 76;  // (float) Интервал уменьшения счётчика выстрелов.
stock const m_usGlock18Event         = 154; // (short) Код события для Glock18.
stock const m_usFamasEvent           = 155; // (short) Код события для Famas.

//////////////////////////////////////////////////////////////////////////
// Оффсеты для оружия, специфичного для каждого типа (например, CC4, гранаты, AK47 и т.д.)

/// Пример для CC4 (бомбы)
stock const m_bArming            = 312; // (int) Флаг активации CC4.
stock const m_bInArmingAnimation = 313; // (int) Флаг анимации активации CC4.
stock const m_flArmedTime        = 79;  // (float) Время активации CC4 (на Linux: возможно +1).

/// CHEGrenade
stock const m_usExplo_HEGREN = 156;      // (short) Код взрыва HE-гранаты.

/// Пример для АК47:
stock const m_iShell_AK47      = 78;  // (int) Количество патронов АК47.
stock const m_fIsDeployed_AK47 = 79;  // (int) Флаг развертывания АК47.
stock const m_usEvent_AK47     = 160; // (short) Код события АК47.

// (Аналогичные определения имеются для CAUG, CDEAGLE, CELITE, CFiveSeven, CM249, CM4A1, MAC10,
// MP5N, P228, P90, SG552, TMP, UMP45, USP, Galil, Glock18, Scout, AWP, SG550, G3SG1 и др.)

//////////////////////////////////////////////////////////////////////////
// Пример для XM1014 (дробовик)
stock const m_iShell_XM1014    = 78;  // (int) Количество патронов.
stock const m_flPumpTime_XM1014 = 79;  // (float) Время перекачки.
stock const m_usEvent_XM1014    = 160; // (int, short) Код события.

//////////////////////////////////////////////////////////////////////////
// Пример для ножа (CKnife)
stock const m_fAllSolid_CKnife    = 78; // (int, bool) Флаг "all-solid".
stock const m_fStartSolid_CKnife  = 79; // (int) Флаг начала "solid" состояния.
stock const m_fInOpen_CKnife      = 80; // (int) Флаг, что нож открыт.
stock const m_fInWater_CKnife     = 81; // (int) Флаг нахождения в воде.
stock const m_flFraction_CKnife   = 82; // (float) Фактор дробления.
stock const m_vecEndPos_CKnife    = {83,84,85}; // (vector) Конечное положение (3 координаты).
stock const m_flPlaneDist_CKnife  = 86; // (float) Расстояние до плоскости.
stock const m_vecPlaneNormal_CKnife[3] = {87,88,89}; // (vector) Нормаль плоскости.
stock const m_pHit_CKnife         = 90; // (указатель) Сущность столкновения.
stock const m_iHitgroup_CKnife    = 91; // (int) Группа попадания.
stock const m_usEvent_CKnife      = 184; // (short) Код события ножа.

//////////////////////////////////////////////////////////////////////////
// Пример для WeaponCycler (переключатель оружия)
stock const m_iszModel_WeaponCycler = 78; // (string) Модель переключателя.
stock const m_iModel_WeaponCycler   = 79; // (int) Идентификатор модели.

//////////////////////////////////////////////////////////////////////////
// Цепочка для игрока (CBaseEntity -> ... -> CBasePlayer) (Linux: +5)
stock const XO_CBASEPLAYER = 5;

// Пример основных полей игрока:
stock const random_seed                     = 96;  // Общий seed для клиента и сервера.
stock const m_hSpectatedTarget              = 98;  // (указатель) Наблюдаемая сущность.
stock const m_hSpectatedTarget_pent         = 392; // (Linux: +20) Указатель на наблюдаемую сущность.
stock const m_hSpectatedTarget_serialnumber = 99;  // (int) Серийный номер наблюдаемой сущности.
stock const m_flNextSpecButtonTime          = 100; // (float) Время до возможности смены наблюдаемого.
stock const m_iSpectatedPlayerWeaponId      = 101; // (int) ID оружия наблюдаемого игрока.
stock const m_fHasSpectatedPlayerC4         = 102; // (int, bool) Флаг наличия CC4 у наблюдаемого.
stock const m_fHasSpectatedPlayerDefuseKit  = 103; // (int, bool) Флаг наличия дефузера.
stock const m_iObserverMode                 = 104; // (int) Режим наблюдения.
stock const m_flAnimationTimeDummy          = 105; // (float) «Фейковое» время анимации.
stock const m_fHasTakenHighDamage           = 107; // (int, bool) Флаг: последний урон > 60.
stock const m_flPainShock                   = 108; // (float) Время реакции на боль.
stock const m_iLastZoom                     = 109; // (int) Последнее значение зума.
stock const m_fResumeZoom                   = 110; // (int, bool) Флаг возобновления зума.
stock const m_flEjectBrass                  = 111; // (float) Время выброса гильз.
stock const m_iKevlarType                   = 112; // (int) Тип брони (kevlar).
stock const m_fHasSurvivedLastRound         = 113; // (int, bool) Флаг выживания в последнем раунде.
stock const m_iTeam                         = 114; // (int) Идентификатор команды.
stock const m_iAccount                      = 115; // (int) Внутренняя валюта игрока.
stock const m_fHasPrimary                   = 116; // (int, bool) Флаг наличия основного оружия.
// ... (и другие поля: указатели на активные предметы, массивы патронов, данные HUD, статус бар, и т.д.)

//////////////////////////////////////////////////////////////////////////
// Остальные структуры (Toggle, Grenade, Hostage, RuleEntity, WeaponBox и пр.)
// Здесь аналогичным образом задаются оффсеты с комментариями и поправками для Linux.
// Например, для переключателей (Toggle):
stock const XO_CBASETOGGLE1 = 4;   // (Linux: +4)
stock const m_toggle_state  = 41;  // (int) Текущее состояние переключателя.
stock const m_flActivateFinished = 42; // (float) Время завершения активации.
stock const m_flMoveDistance  = 43; // (float) Пройденная дистанция.
stock const m_flWait          = 44; // (float) Интервал ожидания до новой активации.
stock const m_flLip           = 45; // (float) Размер выступа (lip).
stock const m_flTWidth        = 46; // (float) Ширина перемещения.
stock const m_flTLength       = 47; // (float) Длина перемещения.
stock const m_vecPosition1[3] = {48,49,50}; // (vector) Начальное положение.
stock const m_vecPosition2[3] = {51,52,53}; // (vector) Конечное положение.
stock const m_vecAngle1[3]    = {54,55,56}; // (vector) Начальные углы.
stock const m_vecAngle2[3]    = {57,58,59}; // (vector) Конечные углы.
stock const m_cTriggersLeft   = 60;         // (int) Количество оставшихся триггеров.
stock const m_flHeight        = 61;         // (float) Высота объекта.
stock const m_hActivator_pent = 248;        // (указатель) Активирующая сущность (Linux: +16).
stock const m_hActivator_serialnumber = 63;   // (int) Серийный номер активатора.
stock const m_pfnCallWhenMoveDone     = 64;     // (указатель) Callback после завершения движения.

stock const XO_CBASETOGGLE2 = 5;  // (Linux: +5 для дополнительных полей Toggle)
stock const m_pfnCallWhenMoveDone_Flag_LIN = 69; // (указатель) Linux-версия callback.
stock const m_vecFinalDest[3]  = {65,66,67}; // (vector) Итоговая конечная позиция.
stock const m_vecFinalAngle[3] = {68,69,70}; // (vector) Итоговые углы.
stock const m_bitsDamageInflict= 71;         // (int) Маска наносимого урона.
stock const m_sMaster          = 72;         // (int) Индекс мастера, управляющего объектом.

// Пример для гранаты (CGrenade):
stock const XO_CGRENADE = 5; // (Linux: +5)
stock const m_bStartDefuse     = 384; // (bool/int) Флаг начала дефузии гранаты/C4.
stock const m_bIsC4            = 385; // (bool) Флаг, указывающий, что объект является C4.
stock const m_hDefuser_pent    = 388; // (указатель) Указатель на дефузера (Linux: +20).
stock const m_hDefuser_serial  = 98;  // (int) Серийный номер дефузера.
stock const m_flDefuseCountDown= 99;  // (float) Обратный отсчёт дефузии.
stock const m_flC4Blow         = 100; // (float) Время срабатывания C4.
stock const m_flNextFreqInterval=101; // (float) Интервал следующего звукового сигнала.
stock const m_flNextBeep       = 102; // (float) Время следующего Beep.
stock const m_flNextFreq       = 103; // (float) Следующая частота.
stock const m_sBeepName        = 104; // (int) Идентификатор звука.
stock const m_fAttenu          = 105; // (float) Коэффициент ослабления звука.
stock const m_flNextBlink      = 106; // (float) Время следующего мигания.
stock const m_flNextDefuseCheck= 107; // (float) Интервал проверки дефузии.
stock const m_bJustBlew        = 108; // (bool) Флаг "только что взорвался".
stock const m_iGrenadeTeam     = 109; // (int) Командный идентификатор гранаты.
stock const m_iC4Hurry         = 110; // (int) Флаг ускоренного срабатывания C4.
stock const m_pentCurBombTarget= 444; // (указатель) Текущая цель для бомбы (Linux: +20).
stock const m_SGSmoke          = 112; // (int) Показатель дымового эффекта.

// Пример для заложника (CHostage):
const XO_CHOSTAGE = 5;  // (Linux: +5)
stock const m_LastActivity         = 96; // (int) Последняя активность заложника.
stock const m_fHasGivenUseBonus     = 97; // (bool) Флаг выдачи бонуса за использование.
stock const m_flNextFlinch         = 99; // (float) Время до следующей реакции (flinch).
stock const m_flNextUse            = 100; // (float) Время до следующей возможности использования.
stock const m_fShouldFollow        = 105; // (bool) Флаг, указывающий, должен ли заложник следовать за игроком.
stock const m_vecOrigOrigin[3]     = {106,107,108}; // (vector) Исходное положение.
stock const m_vecOrigAngles[3]     = {109,110,111}; // (vector) Исходные углы.
stock const m_iCurPathNode         = 174;  // (int) Текущий узел пути.
stock const m_iNextPathNode        = 175;  // (int) Следующий узел пути.
stock const m_flNextPathOrig2D_X   = 176;  // (float) Следующая координата X для пути.
stock const m_flNextPathOrig2D_Y   = 177;  // (float) Следующая координата Y для пути.
stock const m_flPathCheckDelay     = 479;  // (float) Задержка проверки пути.
stock const m_flLastPathCheckTime  = 480;  // (float) Время последней проверки пути.
stock const m_fHasReachedPathNode  = 482;  // (bool) Флаг достижения узла пути.
stock const m_flLastUse            = 483;  // (float) Время последнего использования.
stock const m_iHostageId           = 487;  // (int) Идентификатор заложника.
stock const m_hRescuer             = 490;  // (указатель) Указатель на спасателя.
stock const m_iCzHostageModelIndex = 491;  // (int) Индекс модели заложника.

// Пример для CRuleEntity:
stock const XO_CRULEENTITY = 4;
stock const m_iszMaster    = 34;  // (string) Имя мастера для связи с другими сущностями.

// Пример для CGamePlayerEquip:
const MAX_EQUIP = 32;
stock const XO_CGAMEPLAYEREQUIP = 4;
stock const m_weaponNames[MAX_EQUIP] = {35,36,...}; // (массив int) Индексы для строк имён оружия.
stock const m_weaponCount[MAX_EQUIP] = {67,68,...}; // (массив int) Количество оружия в каждом слоте.

// Пример для коробки с оружием (CWeaponBox):
stock const XO_CWEAPONBOX = 4;
stock const m_rgpPlayerItems_CWeaponBox[6] = {34,35,...}; // (массив указателей) Предметы в коробке с оружием.
stock const m_rgiszAmmo[32] = {40,41,...};  // (массив int) Индексы для строк имён типов патронов.
stock const m_rgAmmo_CWeaponBox[32] = {72,73,...}; // (массив int) Количество патронов по каждому типу.
stock const m_cAmmoTypes = 104; // (int) Общее число типов патронов в коробке.


/****************** ВАЖНО! ******************
Если во время тестирования возникают несоответствия (например, ошибки доступа к pdata или некорректные значения),
проверьте следующие моменты:
  1. Версию движка и платформу (Windows vs. Linux).
  2. Правильность применения поправок (например, +4, +5, +16, +20) для Linux.
  3. Сравните эти оффсеты с официальной документацией или исходниками движка.
***********************************************/
