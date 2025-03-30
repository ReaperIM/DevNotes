# ////////////////OFFSET's///////////////////////
```inc
////////////////OFFSET's///////////////////////

// stock const m_rgpPlayerItems_slot0 = 367
// (Этот оффсет обычно используется для массива предметов игрока – смещение внутри структуры CBasePlayer)

// ------------------------------
// CBaseEntity -> CBaseDelay -> CBaseAnimating
// (Linux: добавляется +4 к базовому указателю)
stock const XO_CBASEANIMATING = 4;  

// Базовое смещение для полей анимации.
stock const m_flFrameRate       = 36; // (float) Частота анимации.
stock const m_flGroundSpeed     = 37; // (float) Скорость объекта по земле.
stock const m_flLastEventCheck  = 38; // (float) Время последней проверки события.
stock const m_fSequenceFinished = 39; // (int) Флаг завершения анимационной последовательности.
stock const m_fSequenceLoops    = 40; // (int) Счётчик или флаг цикличности анимации.

// ------------------------------
// CBaseEntity -> CBaseDelay
// (Linux: +4)
stock const XO_CBASEDELAY = 4;

// Базовое смещение для полей, связанных с задержками выполнения.
stock const m_flDelay       = 34; // (float) Задержка выполнения команды.
stock const m_iszKillTarget = 35; // (индекс строки) Идентификатор цели для команды убить; доступ через get_pdata_string.

// ------------------------------
// CBaseEntity (общие поля)
// (LINUX DIFF 0)
stock const vtbl = 0;      // (указатель) Виртуальная таблица – используется для внутренних вызовов, не изменяется.
stock const _pev = 1;      // (указатель) Адрес структуры pev – содержит приватные переменные объекта.
stock const m_pGoalEnt = 2;  // (указатель) Ссылка на целевую сущность; для работы используйте get_pdata_cbase.
stock const m_pLink = 3;     // (указатель) Ссылка для связи сущностей, например, для списков.

// Функциональные указатели (для динамического поведения объекта). Тип – адрес функции (int).
stock const m_pfnThink            = 4; // Указатель на функцию think (регулярное обновление логики объекта).
stock const m_pfnThink_Flag_LINUX = 5; // Linux‑специфичный флаг для think (если требуется).
stock const m_pfnTouch            = 5; // Указатель на функцию touch (вызывается при столкновении); (LINUX DIFF 1).
stock const m_pfnTouch_Flag_LINUX = 7; // Линукс‑вариант функции touch.
stock const m_pfnUse              = 6; // Указатель на функцию use (вызывается при активации объекта).
stock const m_pfnUse_Flag_LINUX   = 9; // Линукс‑вариант use‑функции.
stock const m_pfnBlocked          = 7; // Указатель на функцию blocked (обработка столкновений).
stock const m_pfnBlocked_Flag_LINUX = 11; // Линукс‑вариант функции blocked.

// ------------------------------
// LINUX DIFF 4 – Таблица боеприпасов
// Эти оффсеты соответствуют индексам таблицы для различных типов патронов.
// При доступе используйте get_pdata_int, так как эти поля – целые числа.
stock const ammo_buckshot_TAB  = 11; // Buckshot (дробовик)
stock const ammo_9mm_TAB       = 13; // 9mm патроны
stock const ammo_556nato_TAB   = 15; // 5.56 Nato
stock const ammo_556natobox_TAB= 17; // Коробка с 5.56 Nato
stock const ammo_762nato_TAB   = 19; // 7.62 Nato
stock const ammo_45acp_TAB     = 21; // 45 ACP
stock const ammo_50ae_TAB      = 23; // 50 AE
stock const ammo_338magnum_TAB = 25; // 338 Magnum
stock const ammo_57mm_TAB      = 27; // 57mm патроны
stock const ammo_357sig_TAB    = 29; // 357 SIG

// Дополнительные поля для thrown объектов (например, гранат)
stock const m_flStartThrow   = 30; // (float) Время начала броска.
stock const m_flReleaseThrow = 31; // (float) Время отпускания оружия/объекта.
stock const m_chargeReady    = 32; // (int, bool) Флаг готовности заряда (например, оружие с зарядкой).
stock const m_fInAttack      = 33; // (int, bool) Флаг, показывающий, что объект находится в состоянии атаки.

// ------------------------------
// CBaseEntity -> CArmoury
// (Linux: +4)
stock const XO_CARMOURY = 4;

// Базовое смещение для полей, связанных с инвентаризацией оружия.
stock const m_iItem       = 34;  // (int) Идентификатор оружия/предмета, определённый в FGD.
stock const m_iCount      = 35;  // (int) Количество предметов до исчезновения сущности.
stock const m_iSaveCount  = 36;  // (int) Сохранённое количество предметов при спавне.
stock const m_bRestoreCount = 148; // (int, bool) Флаг восстановления количества – доступ через get_pdata_int (истолковывается как bool).

// ------------------------------
// CBaseEntity -> CPointEntity -> CMapInfo
stock const XO_CMAPINFO = 4;

// Базовое смещение для полей, связанных с информацией о карте.
stock const m_ibuying      = 34; // (int) Флаг, связанный с покупкой (например, оружия).
stock const m_flBombRadius = 35; // (float) Радиус взрыва бомбы.

// ------------------------------
// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster
// (Linux: +5)
stock const XO_CBASEMONSTER = 5;

// Базовое смещение для полей монстров (NPC).
stock const m_Activity       = 73; // (int) Текущая активность/анимация монстра.
stock const m_IdealActivity  = 74; // (int) Идеальное состояние активности, к которому стремится монстр.
stock const m_LastHitGroup   = 75; // (int) Группа попадания – определяет, куда именно нанесён урон.
stock const m_bitsDamageType = 76; // (int) Битовая маска типов урона (например, огонь, взрыв и пр.).

// NOT OFFSETS LOL
// Эти константы являются индексами для массива pdata значений, связанных с time-based damage.
// Все значения представляют целочисленные поля – для доступа используйте get_pdata_int/set_pdata_int.
enum {
    itbd_Paralyze,     // Паралич
    itbd_NerveGas,     // Нервный газ
    itbd_Poison,       // Отравление
    itbd_Radiation,    // Радиация
    itbd_DrownRecover, // Восстановление после утопления
    itbd_Acid,         // Кислотный урон
    itbd_SlowBurn,     // Медленное горение
    itbd_SlowFreeze,   // Медленное замерзание
    CDMG_TIMEBASED     // Общее количество типов урона
}
stock const m_rgbTimeBasedDamage[CDMG_TIMEBASED] = {308,309,...};
// Пример: 
//   new dmg = get_pdata_int(id, m_rgbTimeBasedDamage[itbd_DrownRecover]);
//   set_pdata_int(id, m_rgbTimeBasedDamage[itbd_Poison], 200);

//////////////////////////////////////////////////////////////////////////
// Состояния монстра (pdata: int или float)
stock const m_MonsterState = 79;         // Текущее состояние монстра (int)
stock const m_IdealMonsterState = 80;      // Идеальное состояние монстра (int)
stock const m_afConditions = 81;           // Текущие флаги условий (int)
stock const m_afMemory = 82;               // "Память" монстра – запомненные флаги (int)
stock const m_flNextAttack = 83;           // Время следующей атаки (float, используйте get_pdata_float)


// Сведения о враге и цельной сущности (pdata: указатели и int)
// Для получения указателей на сущности используйте get_pdata_ent.
stock const m_hEnemy_pent = 336;           // Идентификатор врага («pent»); на Linux смещение может быть +20
stock const m_hEnemy_serialnumber = 85;    // Серийный номер врага (int)
stock const m_hTargetEnt_pent = 344;       // Идентификатор целевой сущности (Linux: +20)
stock const m_hTargetEnt_serialnumber = 87; // Серийный номер целевой сущности (int)


// Дополнительная информация (pdata: float и int)
// Для векторных полей используйте get_pdata_float (массив из 3-х элементов).
stock const m_flFieldOfView = 88;          // Поле зрения монстра (float)
stock const m_bloodColor = 89;             // Цвет крови (int)
stock const m_HackedGunPos[3] = {90,91,92};  // Скорректированная позиция оружия (вектор float)
stock const m_vecEnemyLKP[3] = {93,94,95};   // Последняя известная позиция врага (вектор float)

//////////////////////////////////////////////////////////////////////////
// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem
// (Linux: +4) – базовое смещение для полей игрокового предмета
stock const XO_CBASEPLAYERITEM = 4;
stock const m_pPlayer = 41;   // Указатель на игрока-владельца; для доступа используйте get_pdata_cbase или get_pdata_cbase_safe
stock const m_pNext = 42;     // Указатель на следующий предмет (также cbase-поле)
stock const m_iId = 43;       // Идентификатор предмета (int)

//////////////////////////////////////////////////////////////////////////
// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem -> CBasePlayerWeapon
// (Linux: +4) – смещение для полей оружия игрока
stock const XO_CBASEPLAYERWEAPON = 4;
stock const m_iPlayEmptySound = 44;             // Флаг проигрывания звука при пустом магазине (int)
stock const m_fFireOnEmpty = 45;                // Флаг стрельбы при отсутствии патронов (int)
stock const m_flNextPrimaryAttack = 46;         // Время следующего основного выстрела (float)
stock const m_flNextSecondaryAttack = 47;       // Время следующего вторичного выстрела (float)
stock const m_flTimeWeaponIdle = 48;            // Время перехода в режим простоя (float)
stock const m_iPrimaryAmmoType = 49;            // Индекс основного типа патронов (int)
stock const m_iSecondaryAmmoType = 50;          // Индекс вторичного типа патронов (int)
stock const m_iClip = 51;                       // Количество патронов в магазине (int; -1, если не используется)
stock const m_iClientClip = 52;                 // Последнее значение магазина для клиента (int)
stock const m_iClientWeaponState = 53;          // Состояние оружия для HUD (int)
stock const m_fInReload = 54;                   // Флаг перезарядки (int)
stock const m_fInSpecialReload = 55;            // Флаг специальной перезарядки (например, для дробовика) (int)
stock const m_iDefaultAmmo = 56;                // Количество патронов при спавне оружия (int)
stock const m_iShellLate = 57;                  // Задержка обработки оболочек (int)
stock const m_flWeaponSpeed = 58;               // Скорость работы оружия (float)
stock const m_bDelayFire = 236;                 // Флаг задержки стрельбы (int)

stock const m_iDirection = 60;                  // Направление отдачи (1 = вправо, 0 = влево) (int)
stock const m_flAccuracy = 62;                  // Коэффицент точности оружия (float)
stock const m_flLastFire = 63;                  // Время последнего выстрела (float)
stock const m_iShotFired = 64;                  // Счетчик выстрелов (int)

stock const m_iszViewModel = 68;                // Идентификатор модели вида оружия (строка, используйте get_pdata_string)
stock const m_flGlock18Shoot = 69;              // Интервал стрельбы для Glock18 (float)
stock const m_iGlock18ShotsFired = 70;          // Счетчик выстрелов из Glock18 (int)
stock const m_flFamasShoot = 71;                // Интервал стрельбы для Famas (float)
stock const m_iFamasShotsFired = 72;            // Счетчик выстрелов из Famas (int)
stock const m_flFamasBurstSpread = 73;          // Разброс очереди для Famas (float)
stock const m_fWeaponState = 74;                // Текущее состояние оружия (int)

// Флаги состояния оружия (значения идут с кратными степенями 2)
enum ( <<=1 )
{
    WEAPONSTATE_USP_SILENCED = 1,    // USP с глушителем
    WEAPONSTATE_GLOCK18_BURST_MODE,  // Режим очередной стрельбы Glock18
    WEAPONSTATE_M4A1_SILENCED,       // M4A1 с глушителем
    WEAPONSTATE_ELITE_LEFT,          // Dual Elite в левой руке
    WEAPONSTATE_FAMAS_BURST_MODE,    // Режим очередной стрельбы Famas
    WEAPONSTATE_SHIELD_DRAWN         // Активирован щит
};

stock const m_flNextReload = 75;           // Время следующей перезарядки (float)
stock const m_flDecreaseShotsFired = 76;   // Интервал уменьшения счетчика выстрелов (float)
stock const m_usGlock18Event = 154;          // Код события для Glock18 (short; работайте через pdata как int)
stock const m_usFamasEvent = 155;            // Код события для Famas (short)

//////////////////////////////////////////////////////////////////////////
// Weapon Specific (свойства для конкретного оружия)
// Если указано "Linux +4", то для Linux-сборок необходимо прибавлять это смещение.

/// CC4 (бомба)
stock const m_bArming = 312;               // Флаг активации CC4 (int) (на Linux: смещение 82)
stock const m_bInArmingAnimation = 313;     // Флаг, что идет анимация активации CC4 (int)
stock const m_flArmedTime = 79;             // Время активации CC4 (float) (на Linux: смещение 83)
// Примечание: зарезервированные поля (_dummy_) могут следовать за этими значениями.

/// CHEGrenade
stock const m_usExplo_HEGREN = 156;         // Код взрыва HE-гранаты (short)

/// CAK47
stock const m_iShell_AK47 = 78;             // Количество оболочек АК47 (int)
stock const m_fIsDeployed_AK47 = 79;        // Флаг развертывания АК47 (int)
stock const m_usEvent_AK47 = 160;           // Код события АК47 (short)

/// CAUG
stock const m_iShell_AUG = 78;              // Количество оболочек AUG (int)
stock const m_fIsDeployed_AUG = 79;         // Флаг развертывания AUG (int)
stock const m_usEvent_AUG = 160;            // Код события AUG (short)

/// CDEAGLE
stock const m_iShell_DEAGLE = 78;           // Количество оболочек Desert Eagle (int)
stock const m_usEvent_DEAGLE = 158;         // Код события DEAGLE (short)

/// CELITE
stock const m_iShell_ELITE = 78;            // Количество оболочек Double Elite (int)
stock const m_usEventLeft_ELITE = 158;      // Код события для левой стороны Elite (short)
stock const m_usEventRigth_ELITE = 159;     // Код события для правой стороны Elite (short)
    
/// CFiveSeven
stock const m_iShell_FiveSeven = 78;        // Количество оболочек Five-Seven (int)
stock const m_usEvent_FiveSeven = 158;      // Код события Five-Seven (short)
    
/// CM249
stock const m_iShell_CM249 = 78;            // Количество оболочек M249 (int)
stock const m_fIsDeployed_CM249 = 79;       // Флаг развертывания M249 (int)
stock const m_usEvent_CM249 = 160;          // Код события M249 (short)
    
/// CM4A1
stock const m_iShell_M4A1 = 78;             // Количество оболочек M4A1 (int)
stock const m_fIsDeployed_M4A1 = 79;        // Флаг развертывания M4A1 (int)
stock const m_usEvent_M4A1 = 160;           // Код события M4A1 (short)
    
/// CMAC10
stock const m_iShell_MAC10 = 78;            // Количество оболочек MAC10 (int)
stock const m_fIsDeployed_MAC10 = 79;       // Флаг развертывания MAC10 (int)
stock const m_usEvent_MAC10 = 160;          // Код события MAC10 (short)
    
/// CMP5N
stock const m_iShell_CMP5N = 78;            // Количество оболочек MP5N (int)
stock const m_fIsDeployed_CMP5N = 79;       // Флаг развертывания MP5N (int)
stock const m_usEvent_CMP5N = 160;          // Код события MP5N (short)
    
/// CP228
stock const m_iShell_P228 = 78;             // Количество оболочек P228 (int)
stock const m_usEvent_P228 = 158;           // Код события P228 (short)
    
/// CP90
stock const m_iShell_P90 = 78;              // Количество оболочек P90 (int)
stock const m_fIsDeployed_P90 = 79;         // Флаг развертывания P90 (int)
stock const m_usEvent_P90 = 160;            // Код события P90 (short)
    
/// CSG552
stock const m_iShell_SG552 = 78;            // Количество оболочек SG552 (int)
stock const m_fIsDeployed_SG552 = 79;       // Флаг развертывания SG552 (int)
stock const m_usEvent_SG552 = 160;          // Код события SG552 (short)
    
/// CTMP
stock const m_iShell_TMP = 78;              // Количество оболочек TMP (int)
stock const m_fIsDeployed_TMP = 79;         // Флаг развертывания TMP (int)
stock const m_usEvent_TMP = 160;            // Код события TMP (short)
    
/// CUMP45
stock const m_iShell_UMP45 = 78;            // Количество оболочек UMP45 (int)
stock const m_fIsDeployed_UMP45 = 79;       // Флаг развертывания UMP45 (int)
stock const m_usEvent_UMP45 = 160;          // Код события UMP45 (short)
    
/// CUSP
stock const m_iShell_USP = 78;              // Количество оболочек USP (int)
stock const m_usEvent_USP = 158;            // Код события USP (short)
    
/// CGalil
stock const m_iShell_Galil = 78;            // Количество оболочек Galil (int)
stock const m_fIsDeployed_Galil = 79;       // Флаг развертывания Galil (int)
stock const m_usEvent_Galil = 160;          // Код события Galil (short)
    
/// CFamas
stock const m_iShell_Famas = 78;            // Количество оболочек Famas (int)
stock const m_fIsDeployed_Famas = 79;       // Флаг развертывания Famas (int)
    
/// CGLOCK18
stock const m_iShell_GLOCK18 = 78;          // Количество оболочек Glock18 (int)
stock const m_fIsDeployed_GLOCK18 = 79;     // Флаг развертывания Glock18 (int)
    
/// CSmokeGrenade
stock const m_usSmokeExplo = 156;          // Код взрыва дымовой гранаты (short)
    
/// CSCOUT
stock const m_iShell_SCOUT = 78;           // Количество оболочек Scout (int)
stock const m_usEvent_SCOUT = 158;         // Код события Scout (short)
    
/// CAWP
stock const m_iShell_AWP = 78;             // Количество оболочек AWP (int)
stock const m_usEvent_AWP = 158;           // Код события AWP (short)
    
/// CSG550
stock const m_iShell_SG550 = 78;           // Количество оболочек SG550 (int)
stock const m_usEvent_SG550 = 158;         // Код события SG550 (short)
    
/// CG3SG1
stock const m_iShell_G3SG1 = 78;           // Количество оболочек G3SG1 (int)
stock const m_usEvent_G3SG1 = 158;         // Код события G3SG1 (short)

// CFlashbang 
// (Нет приватных данных)

// -----------------------------
// CXM1014
// -----------------------------
// Shell count (int). Используйте get_pdata_int/set_pdata_int.
stock const m_iShell_XM1014    = 78;
// Pump time (float). Используйте get_pdata_float/set_pdata_float.
stock const m_flPumpTime_XM1014 = 79;
// Event code (int, обычно short). Используйте get_pdata_int/set_pdata_int.
stock const m_usEvent_XM1014    = 160;

// -----------------------------
// CM3
// -----------------------------
// Shell count (int).
stock const m_iShell_CM3       = 78;
// Pump time (float).
stock const m_flPumpTime_CM3   = 79;
// Event code (int).
stock const m_usEvent_CM3      = 160;

// -----------------------------
// CC4
// -----------------------------
// Flag: C4 is arming (int: 0 или 1). Для доступа – get_pdata_int.
stock const m_bArming_C4          = 312;
// Flag: C4 is in arming animation (int).
stock const m_bInArmingAnimation_C4 = 313;
// Time when C4 is armed (float). Используйте get_pdata_float.
stock const m_flArmedTime_C4      = 79;

// -----------------------------
// CKnife
// -----------------------------
// All-solid flag (int, фактически bool).
stock const m_fAllSolid_CKnife    = 78;
// Start solid flag (int).
stock const m_fStartSolid_CKnife  = 79;
// Flag: knife is in open state (int).
stock const m_fInOpen_CKnife      = 80;
// Flag: knife is in water (int).
stock const m_fInWater_CKnife     = 81;
// Fraction value (float).
stock const m_flFraction_CKnife   = 82;
// End position vector (float[3]). Для каждого элемента используйте get_pdata_float.
stock const m_vecEndPos_CKnife    = {83,84,85};
// Plane distance (float).
stock const m_flPlaneDist_CKnife  = 86;
// Plane normal vector (float[3]).
stock const m_vecPlaneNormal_CKnife[3] = {87,88,89};
// Pointer to the entity that was hit (pdata – указатель). Используйте get_pdata_ent.
stock const m_pHit_CKnife       = 90;
// Hit group (int).
stock const m_iHitgroup_CKnife  = 91;
// Event code for knife (int).
stock const m_usEvent_CKnife    = 184;

// -----------------------------
// CWeaponCycler
// -----------------------------
// Model string (indexed, строка). Используйте get_pdata_string.
stock const m_iszModel_WeaponCycler = 78;
// Model id (int).
stock const m_iModel_WeaponCycler   = 79;

// -----------------------------
// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster -> CBasePlayer 
// (Linux: +5; базовое смещение для игрока)
// -----------------------------
stock const XO_CBASEPLAYER = 5;

// random_seed: общий seed (int), используемый как между клиентом, так и сервером.
stock const random_seed = 96;
// Field 97 – неизвестно.
stock const m_hSpectatedTarget = 98;  
// Spectated target storage: для Linux m_hSpectatedTarget работает в паре с m_hSpectatedTarget_serialnumber.
// Получить указатель используйте get_pdata_ent.
stock const m_hSpectatedTarget_pent = 392;  // (Linux: +20)
stock const m_hSpectatedTarget_serialnumber = 99;  // серийный номер (int)
stock const m_flNextSpecButtonTime = 100;  // (float): время следующей возможности смены наблюдаемого
stock const m_iSpectatedPlayerWeaponId = 101;  // (int) ID оружия наблюдаемого игрока
stock const m_fHasSpectatedPlayerC4 = 102;  // (int, bool) наличие C4 у наблюдаемого
stock const m_fHasSpectatedPlayerDefuseKit = 103;  // (int, bool) наличие дефузера
stock const m_iObserverMode = 104;  // (int) режим наблюдения
stock const m_flAnimationTimeDummy = 105;  // (float) «фейковое» время анимации
// Field 106 – неизвестно.
stock const m_fHasTakenHighDamage = 107;  // (int, bool) флаг: последний урон > 60
stock const m_flPainShock = 108;  // (float) время боли/шока
stock const m_iLastZoom = 109;  // (int) последнее значение зума
stock const m_fResumeZoom = 110;  // (int, bool) флаг возобновления зума
stock const m_flEjectBrass = 111;  // (float) время выброса гильз
stock const m_iKevlarType = 112;  // (int) тип брони (kevlar)
stock const m_fHasSurvivedLastRound = 113;  // (int, bool)
stock const m_iTeam = 114;  // (int) идентификатор команды
stock const m_iAccount = 115;  // (int) внутренняя валюта игрока
stock const m_fHasPrimary = 116;  // (int, bool) наличие основного оружия
// Field 117 – неизвестно (резерв для анимации)
stock const m_iAnimationInCaseDie = 118;  
/* m_iAnimationInCaseDie:
   Используется при смерти для установки дополнительных параметров (скорости, направления и т.д.).
*/
stock const m_flNextChatTime = 119;  // (float) время до возможности отправить следующее сообщение
// Поле 120 – был m_bHasReceivedDefItems, теперь с индексом 480 (bool).
stock const m_bHasReceivedDefItems = 480;
// Joined state (int).
stock const m_iJoinedState = 121;
// Pointer на последний использованный объект камеры; доступ – get_pdata_cbase_safe.
stock const m_pLastCamera = 122;
// Время следующей смены камеры (float).
stock const m_flNextCameraTime = 123;
// Время последней активности (float).
stock const m_flLastActivityTime = 124;
// Поля 125 – объединённые флаги: m_bHasBeenShownBriefing и m_bHasChangeTeamThisRound
stock const m_bHasBeenShownBriefing = 500;  // (bool)
stock const m_bHasChangeTeamThisRound = 501; // (bool)
stock const m_iInternalModel = 126;  // (int) внутренний идентификатор модели
stock const m_iTeamMateKills = 127;  // (int) убийства товарищей по команде
stock const m_iIgnoreMessages = 128;  // (int) флаг игнорирования сообщений
// NVG-related booleans:
stock const m_bGotNVG = 516;            // (bool) наличие Ночного Видения (NVG)
stock const m_bIsNVGSwitchedOn = 517;     // (bool) включено ли NVG

// --- BIG HOLE --- (дальнейшие поля для игрока)
stock const m_flNextIdleCheckTime = 190;  // (float) время следующей проверки бездействия
stock const m_flNextRadioGameTime = 191;  // (float) время следующей команды радио
stock const m_iRadiosLeft = 192;          // (int) количество оставшихся радио
// Поля 193 – флаги: m_bHasSilentRadio, m_bHasC4, m_bHasDefuser, m_bBlastDamaged
stock const m_bHasSilentRadio = 772;   // (bool)
stock const m_bHasC4 = 773;            // (bool)
stock const m_bHasDefuser = 774;       // (bool)
stock const m_bBlastDamaged = 775;     // (bool)
// Relative inflictor origin vector (3 floats).
stock const m_vecRelativeInflictorOrigin[3] = {194,195,196};
stock const m_bTruOnHeDamage = 197;  // (int) флаг обработки HE-урона
stock const m_fNextHudTextArgsGameTime = 198;  // (float) время следующего обновления HUD-текста
// HudTextArgs (мелкий блок)
stock const m_bitHudTextArgsDontResend = 204;  // (int) битовый флаг для HUD-текста
stock const m_iMenu = 205;                     // (int) индекс активного меню
// Поля 206-207 – неизвестны
stock const m_fIsVIPRescued = 208;  // (int, bool) флаг, спасён ли VIP
// Поля 209 – флаги: m_bHasReachRescueEscapePoint, m_bIsVip
stock const m_bHasReachRescueEscapePoint = 836;  // (bool)
stock const m_bIsVip = 837;                      // (bool)
stock const m_flNextRadarUpdateTime = 210;  // (float) время следующего обновления радара
stock const m_vecLastPosition[3] = {211,212,213};  // (float[3]) последняя позиция игрока
stock const m_iVoteKickUserId = 214;        // (int) user id кандидата на votekick
stock const m_flNextVote = 215;             // (float) время следующей возможности голосования
stock const m_iTeamKills = 216;             // (int) количество командных убийств
stock const m_iHostageKills = 217;          // (int) количество убийств заложников
stock const m_iVotedMapIndex = 218;         // (int) индекс выбранной карты
stock const m_bIsPrimaryFireAllowed = 219;  // (bool) разрешена ли основная стрельба
stock const m_flLastAttackTime = 220;       // (float) время последней атаки
stock const m_flNextTeammateAttackAlert = 221;// (float) время следующего предупреждения о атаке союзника
// Поле 222 – флаги о способе убийства
stock const m_bKilledByHeadShot = 888;         // (bool) убит ли выстрелом в голову
stock const m_bKilledBecauseTeamKill = 889;    // (bool) убит ли по причине командного убийства
stock const m_bNotReceivingMoneyNextRound = 890; // (bool) не получает ли деньги в следующем раунде
stock const m_flNextTimeleft = 223;          // (float) время оставшегося таймера
stock const m_boolChangeNameAtRespawn = 896; // (bool) менять имя при респавне
stock const m_szNewName = 897;               // (string, длина 32) новое имя; используйте get_pdata_string/set_pdata_string
stock const m_fBombDefusing = 232;           // (int) флаг дефузии (бит, напр. 1<<8)
stock const m_bBombDefusing = 929;           // (bool) состояние дефузии; доступ через get_pdata_int
stock const m_flNextMapZoneTime = 233;       // (float) время следующего обновления зоны карты
stock const m_fMapZone = 234;                // (int) флаг зоны карты
stock const m_fClientMapZone = 235;          // (int) клиентский флаг зоны карты
stock const m_pentBombTarget = 236;          // (указатель) на сущность цели для бомбы; используйте get_pdata_ent
stock const m_iPlayerSound = 237;            // (int) индекс звукового слота игрока
stock const m_iTargetVolume = 238;           // (int) идеальная громкость игрока
stock const m_iWeaponVolume = 239;           // (int) текущая громкость звука оружия
stock const m_iExtraSoundTypes = 240;        // (int) дополнительная классификация звука оружия
stock const m_iWeaponFlash = 241;            // (int) яркость вспышки оружия
stock const m_flStopExtraSoundTime = 242;    // (float) время остановки дополнительного звука
stock const m_flFlashLightTime = 243;        // (float) время до следующей разрядки фонарика
stock const m_iFlashBattery = 244;           // (int) уровень заряда фонарика
stock const m_afButtonLast = 245;            // (int) последнее состояние кнопок
stock const m_afButtonPressed = 246;         // (int) кнопки, которые были нажаты
stock const m_afButtonReleased = 247;        // (int) кнопки, которые были отпущены
stock const m_pentSndLast = 248;             // (указатель) последняя звуковая сущность, изменившая тип комнаты; используйте get_pdata_ent
stock const m_flSndRoomtype = 249;           // (float) последний установленный тип комнаты звука
stock const m_flSndRange = 250;              // (float) расстояние от игрока до звуковой сущности
stock const m_flFallVelocity = 251;          // (float) скорость падения игрока

// constant items
enum {
    ITEM_HEALTHKIT = 1,    // ID аптечки
    ITEM_ANTIDOTE = 2,     // ID антидота
    ITEM_SECURITY = 3,     // ID охранного предмета (например, пропуск)
    ITEM_BATTERY = 4,      // ID батарейки
    MAX_ITEMS = 5          // Общее количество типов предметов (жёстко закодировано)
}
// Массив индексов pdata для предметов; доступ через get_pdata_int/set_pdata_int.
stock const m_rgItems[MAX_ITEMS] = { 252 , 253 , ... };


// Флаги физики (int). Определяют, когда «нормальная» физика должна быть пересмотрена.
stock const m_afPhysicsFlags = 257;

// Время (float), после которого игрок может снова использовать команду суицида.
stock const m_fNextSuicideTime = 258;

// Время (float) последнего звука шагов.
stock const m_flTimeStepSound = 259;

// Время (float) до проигрывания следующей idle-анимации оружия у игрока.
stock const m_flTimeWeaponIdle_PLAYER = 260;

// Время (float), сколько игрок находится под водой.
stock const m_flSwimTime = 261;

// Время (float), сколько игрок приседает.
stock const m_flDuckTime = 262;

// Время (float) до следующего walljump.
stock const m_flWallJumpTime = 263;

// Время (float) следующего обновления скафандра (suit update).
stock const m_flSuitUpdate = 264;

// Список предложенных реплик для обновления скафандра – массив из 4 int-значений.
stock const m_rgSuitPlayList[4] = {265,266,...}; // [CSUITPLAYLIST]

// Номер следующей реплики для очереди обновления скафандра (int).
stock const m_iSuitPlayNext = 269;

// Список индексов реплик, которые не должны повторяться – массив из 32 int.
stock const m_rgiSuitNoRepeat[32] = {270,271,...}; // [CSUITNOREPEAT]

// Время ожидания (float) для каждого no-repeat элемента – массив из 32 значений.
stock const m_rgflSuitNoRepeatTime[32] = {302,303,...};

// Последнее полученное количество урона (int).
stock const m_lastDamageAmount = 334;

// Таймер time-based damage (int).
stock const m_tbdPrev = 335;

// Расстояние (float) до ближайшего источника радиации.
stock const m_flgeigerRange = 336;

// Задержка (float) между обновлениями геигера.
stock const m_flgeigerDelay = 337;

// Предыдущее значение геигер-радиуса (int).
stock const m_igeigerRangePrev = 338;

// Порядковый номер шага (int): определяет, какой звук левой/правой ноги проигрывать.
stock const m_iStepLeft = 339;

// Текущее имя текстуры (string). Используйте get_pdata_string/set_pdata_string.
stock const m_szTextureName = 1360; // m_szTextureName[19] — максимальная длина

// Тип текстуры (char), можно использовать альтернативное поле (например, 344).
stock const m_chTextureType = 1379;

// Количество урона от утопления (int).
stock const m_idrowndmg = 345;

// Восстановленный урон от утопления (int).
stock const m_idrownrestored = 346;

// Биты урона для текущего HUD (int); отправляются клиенту.
stock const m_bitsHUDDamage = 347;

// Флаг (int, bool): требуется отправить отложенное сообщение для перезапуска HUD.
stock const m_fInitHUD = 348;

// Флаг (int, bool): HUD игры инициализирован.
stock const m_fGameHUDInitialized = 349;

// Позиция управления поездом (int).
stock const m_iTrain = 350;

// Флаг (int, bool): если FALSE, происходит сброс информации HUD для текущего оружия.
stock const m_fWeapon = 351;

// Указатель на танк (pdata – используйте get_pdata_ent).
stock const m_pTank = 352;

// Указатель на танк (linux: базовое смещение +20; pdata).
stock const m_pTank_pent = 1408;

// Серийный номер танка (int).
stock const m_pTank_serialnumber = 353;

// Время смерти игрока (float); используется в PlayerDeathThink().
stock const m_fDeadTime = 354;

// Флаг (int, bool) для отладки: если установлен, игрок не издаёт звуки.
stock const m_fNoPlayerSound = 355;

// Флаг (int, bool): наличие модуля длинного прыжка.
stock const m_fLongJump = 356;

// Таймер или флаг для режима скрытности (int).
stock const m_tSneaking = 357;

// Счетчик (int) для обновления HUD; количество тиков между обновлениями.
stock const m_iUpdateTime = 358;

// Известное клиенту здоровье (int).
stock const m_iClientHealth = 359;

// Известный клиенту уровень батареи (int).
stock const m_iClientBattery = 360;

// Флаг (int) сокрытия HUD (например, информация об оружии).
stock const m_iHideHUD = 361;

// Клиентская версия флага скрытия HUD (int).
stock const m_iClientHideHUD = 362;

// Поле обзора (FOV) (int).
stock const m_iFOV = 363;

// Клиентское известное поле обзора (int).
stock const m_iClientFOV = 364;

// Количество спаунов (int).
stock const m_iSpawnCount = 365;

// Указатель на связанную сущность (pdata – применяется get_pdata_cbase / get_pdata_ent).
stock const m_pLinkedEntity = 366;

// Массив указателей на предметы игрока (6 элементов, pdata – с индексами, см. тип CBasePlayer).
stock const m_rgpPlayerItems_CBasePlayer[6] = { 367 , 368 , ... };

// Указатель на активный (текущий) предмет/оружие (pdata: cbase).
stock const m_pActiveItem = 373;

// Клиентская версия указателя активного предмета (pdata).
stock const m_pClientActiveItem = 374;

// Указатель на последний активный предмет (pdata).
stock const m_pLastItem = 375;

// Массив количества патронов для каждого слота (32 элемента, int).
stock const m_rgAmmo_CBasePlayer[32] = {376,377,...};  // [MAX_AMMO_SLOTS]

// Массив предыдущих значений патронов (32 элемента, int).
stock const m_rgAmmoLast[32] = {408,409,...};

// Автонаводящий вектор (vector из 3 float элементов).
stock const m_vecAutoAim[3] = {440,441,442};

// Флаг попадания по автонаведению (int, bool).
stock const m_fOnTarget = 443;

// Счётчик смертей игрока (int).
stock const m_iDeaths = 444;

// Количество кадров до респауна (int); используется в PlayerDeathThink().
stock const m_iRespawnFrames = 445;

// -----------------------------------------------------
// Status Bar (HUD) и связанные с ним поля:
enum sbar_data
{
    SBAR_ID_TARGETCLASS = 1,
    SBAR_ID_TARGEINDEX,
    SBAR_ID_TARGETHEALTH,
    SBAR_END,
};
stock const m_izSBarState[SBAR_END] = {446,447,448}; // Состояние HUD-строк (индексы)
stock const m_flNextSBarUpdateTime = 449;            // Время следующего обновления status bar (float)
stock const m_flStatusBarDisappearDelay = 450;        // Задержка перед исчезновением status bar (float)
stock const m_SbarString0 = 1804;    // Начало строки status bar (string), размер [SBAR_STRING_SIZE]

// Координаты предыдущего обновления прицеливания (int).
stock const m_lastx = 483;
stock const m_lasty = 484;

// Количество кадров для спецэффекта кастомного спрея (int).
stock const m_nCustomSprayFrames = 485;

// Время до следующего спрея (decal) (float).
stock const m_flNextDecalTime = 486;

// Название команды игрока (string); обычно длина 16 символов.
stock const m_szTeamName = 1948;

// Индекс модели игрока (string).
stock const g_szModelIndexPlayer = 491;

// Расширение анимации щита (string, длина 32).
stock const m_szShieldAnimExt = 1968;

// -----------------------------------------------------
// Gait (движение) и углы
stock const m_iGaitSequence = 500;     // Номер анимации ходьбы (int)
stock const m_flGaitFrame = 501;       // Текущий кадр анимации (float)
stock const m_flGaitYaw = 502;         // Значение yaw для анимации ходьбы (float)
stock const m_flPrevGaitOrigin[3] = {503,504,505}; // Предыдущая позиция (vector)
stock const m_flPitch = 506;           // Угол pitch (float)
stock const m_flYaw = 507;             // Угол yaw (float)
stock const m_flGaitMovement = 508;    // Длина перемещения при анимации ходьбы (float)
stock const m_fAutoWeaponSwitch = 509; // Флаг автоматической смены оружия (int)

// -----------------------------------------------------
// Настройки пользователя и щита (bool-поля; используйте get_pdata_int для логических значений)
stock const m_bHasVGUIMenus = 2040;
stock const m_bHasAutoHelp = 2041;
stock const m_bUsesShield = 2042;
stock const m_bHasShield = 2043;

// Флаг для наблюдателя (bool).
stock const m_fObserverHasTarget = 511;

// Время поиска следующего игрока (float).
stock const m_flFindNextPlayerTime = 512;
// Множитель delta-time (float).
stock const m_flDtMultiplier = 513;

// Эффекты ослепления (flash):
stock const m_flFlashedUntil = 514;      // Время до окончания эффекта (float)
stock const m_flFlashedAt = 515;         // Время, когда эффект сработал (float)
stock const m_flFlashHoldTime = 516;     // Время удержания эффекта (float)
stock const m_flFlashDuration = 517;     // Общая длительность эффекта (float)
stock const m_iFlashAlpha = 518;         // Прозрачность эффекта (int)

// Время следующего автофоллоу (float).
stock const m_flNextAutoFollowTime = 519;

// Данные для автопокупок (string). Обычно массив длиной 256 символов.
stock const m_szAutoBuyData = 2080;

// Ребай (повторная покупка) – указатель на структуру; доступ через get_pdata_cbase.
stock const m_pSomeRebuyPointer = 584;
// Последние купленные предметы/патроны:
stock const m_iRebuyLastPrimaryItemId = 585;
stock const m_iRebuyLastPrimaryItemAmmo = 586;
stock const m_iRebuyLastSecondaryItemId = 587;
stock const m_iRebuyLastSecondaryItemAmmo = 588;
stock const m_iRebuyHEGrenadeAmmo = 589;
stock const m_iRebuyFlashbangAmmo = 590;
stock const m_iRebuySmokeGrenadeAmmo = 591;
stock const m_iRebuyDefuseKit = 592;
stock const m_iRebuyNVGoggles = 593;
stock const m_iRebuyKevlar = 594;
stock const m_bRebuyStructBuilt = 595;

// Обновление местоположения игрока:
stock const m_flNextLocationUpdateTime = 596;
stock const m_szLocation = 2388; // Строка местоположения (обычно 28 символов)

// Настройка progress bar:
stock const m_flProgressBarStartTime = 605;
stock const m_flProgressBarEndTime = 606;

// Для наблюдателей: флаги автоматического режиссёрства и возможности смены режима.
stock const m_bObserverAutoDirector = 2428;
stock const m_bCanSwitchObserverModes = 2429;

// Дополнительные поля:
stock const m_flIntense = 609;         // (float) значение интенсивности (используется в CBasePlayer::Intense)
stock const m_flNextSayTime = 612;       // Время до следующего сообщения (float)
stock const m_flNextSayTeamTime = 613;   // Время до следующего сообщения в команде (float)
stock const m_flNextFullUpdateTime = 614; // Время следующего полного обновления (float)
stock const m_flNextVoteTimeCmd = 615;   // Время до следующей команды голосования (float)
stock const m_flNextVoteMapTime = 616;   // Время до следующего голосования за карту (float)
stock const m_flNextListMapsTime = 617;  // Время до обновления списка карт (float)
stock const m_flNextListPlayersTime = 618; // Время до обновления списка игроков (float)
stock const m_flNextNightVisionTime = 619; // Время до следующего переключения ночного видения (float)

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle
// Linux: базовое смещение +4
stock const XO_CBASETOGGLE1 = 4;  
  // Базовое смещение для полей переключателя (Toggle) внутри цепочки анимации.

stock const m_toggle_state = 41;         // (int) Состояние переключения.
stock const m_flActivateFinished = 42;     // (float) Время завершения активации.
stock const m_flMoveDistance = 43;         // (float) Пройденная дистанция во время движения.
stock const m_flWait = 44;                 // (float) Интервал ожидания перед новой активацией.
stock const m_flLip = 45;                  // (float) Размер выступа (lip) объекта.
stock const m_flTWidth = 46;               // (float) Ширина перемещения (TWidth).
stock const m_flTLength = 47;              // (float) Длина перемещения (TLength).
stock const m_vecPosition1[3] = {48,49,50};  // (vector - 3 float) Первая позиция (например, начальное положение).
stock const m_vecPosition2[3] = {51,52,53};  // (vector) Вторая позиция (например, конечное положение).
stock const m_vecAngle1[3] = {54,55,56};     // (vector) Начальные углы (rotation).
stock const m_vecAngle2[3] = {57,58,59};     // (vector) Конечные углы.
stock const m_cTriggersLeft = 60;          // (int) Число оставшихся триггеров.
stock const m_flHeight = 61;               // (float) Высота объекта.
// m_hActivator_pent – указатель на активирующую сущность. Для доступа используйте get_pdata_ent.
// Для Linux: дополнительно +16 к базовому смещению.
stock const m_hActivator_pent = 248;       // (указатель, pdata) Активатор (linux + 16).
stock const m_hActivator_serialnumber = 63; // (int) Серийный номер активатора.
stock const m_pfnCallWhenMoveDone = 64;      // (указатель) Функция обратного вызова, вызываемая по завершении движения.


// Linux: базовое смещение +5 для дополнительных полей Toggle
stock const XO_CBASETOGGLE2 = 5;

stock const m_pfnCallWhenMoveDone_Flag_LIN = 69; // (указатель) Linux-версия callback-функции (diff 0).
stock const m_vecFinalDest[3] = {65,66,67};       // (vector) Итоговая конечная позиция объекта.
stock const m_vecFinalAngle[3] = {68,69,70};        // (vector) Итоговые углы объекта.
stock const m_bitsDamageInflict = 71;              // (int) Битовая маска наносимого урона.
stock const m_sMaster = 72;                        // (int) Индекс мастера, управляющего объектом.


// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster -> CGrenade
// (Linux: базовое смещение +5)
stock const XO_CGRENADE = 5;

stock const m_bStartDefuse = 384;      
  // (bool/int) Флаг начала дефузии гранаты/C4; используйте get_pdata_int/set_pdata_int.
stock const m_bIsC4 = 385;             
  // (bool) Флаг, указывающий, что объект является C4.
stock const m_hDefuser_pent = 388;     
  // (указатель) Указатель на дефузера; для Linux – добавить +20; доступ через get_pdata_ent.
stock const m_hDefuser_serial = 98;    // (int) Серийный номер дефузера.
stock const m_flDefuseCountDown = 99;  // (float) Обратный отсчёт дефузии.
stock const m_flC4Blow = 100;          // (float) Время срабатывания C4.
stock const m_flNextFreqInterval = 101;// (float) Интервал следующей частоты для звука.
stock const m_flNextBeep = 102;        // (float) Время следующего звукового сигнала.
stock const m_flNextFreq = 103;        // (float) Следующая частота сигнала.
stock const m_sBeepName = 104;         // (int) Идентификатор звука (обычно через pdata как int).
stock const m_fAttenu = 105;           // (float) Коэффициент ослабления звука.
stock const m_flNextBlink = 106;       // (float) Время до следующего мигания (визуальный эффект).
stock const m_flNextDefuseCheck = 107; // (float) Интервал проверки дефузии.
stock const m_bJustBlew = 108;         // (bool) Флаг "только что взорвался".
stock const m_iGrenadeTeam = 109;      // (int) Командный идентификатор гранаты.
stock const m_iC4Hurry = 110;          // (int) Флаг/значение ускоренного срабатывания C4.
stock const m_pentCurBombTarget = 444; 
  // (указатель, pdata) Текущая цель для бомбы; для Linux – +20; используйте get_pdata_ent.
stock const m_SGSmoke = 112;         // (int) Показатель дымового эффекта (Smoke grenade).

// _dummy_113 – зарезервировано (unknown offset)

stock const m_usEvent_Grenade = 228; 
  // (short, через pdata как int) Код события для гранаты.
stock const m_bLightSmoke = 458;     
  // (bool) Флаг лёгкого дымового эффекта (pdata_bool).
// m_b459 – зарезервировано: устанавливается в 1 при взрыве дымовой гранаты.

stock const m_vecAngles[3] = {115,116,117};   
  // (vector) Углы объекта гранаты.
stock const m_iRebundsCount = 118;             
  // (int) Счётчик повторных запусков (или аналогичное значение).
stock const m_fSoundSet = 119;                 
  // (float) Параметр настройки звука.


// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster -> CHostage
const XO_CHOSTAGE = 5;  
  // Базовое смещение для заложников (Hostage).

stock const m_LastActivity = 96;      
  // (int) Последняя активность (Activity) заложника.
stock const m_fHasGivenUseBonus = 97;   
  // (bool) Флаг: бонус за использование уже выдан.
  // _hostage_dummy_98 – зарезервировано.
stock const m_flNextFlinch = 99;        
  // (float) Время до следующей реакции (flinch).
stock const m_flNextUse = 100;         
  // (float) Время до следующей возможности использования (action).
  // _hostage_dummy_101 ... _104 – зарезервировано.
stock const m_fShouldFollow = 105;     
  // (bool) Флаг, указывающий, должен ли заложник следовать за игроком.
stock const m_vecOrigOrigin[3] = {106,107,108};  
  // (vector) Исходное положение (RestorePos).
stock const m_vecOrigAngles[3] = {109,110,111};  
  // (vector) Исходные углы (RestorePos).
  // _hostage_dummy_112 … _hostage_dummy_115_171 – зарезервировано.
  // _hostage_dummy_173 (указатель CBasePlayer*) – зарезервировано.
stock const m_iCurPathNode = 174;      
  // (int) Текущий узел пути (CLocalNav*) – смещение 178.
stock const m_iNextPathNode = 175;     
  // (int) Следующий узел пути – смещение 179.
stock const m_flNextPathOrig2D_X = 176;  
  // (float) Следующая координата X для пути (2D) – смещение 180.
stock const m_flNextPathOrig2D_Y = 177;  
  // (float) Следующая координата Y – смещение 181.
  // _hostage_dummy_178_473 – зарезервировано.
  // _hostage_dummy_476_477 – резерв (EHANDLE).
  // _hostage_dummy_478 – резерв (float).
stock const m_flPathCheckDelay = 479;  
  // (float) Задержка проверки пути – смещение 483.
stock const m_flLastPathCheckTime = 480;
  // (float) Время последней проверки пути – смещение 484.
  // _hostage_dummy_481 – зарезервировано (смещение 485).
stock const m_fHasReachedPathNode = 482;  
  // (bool) Флаг: заложник достиг узла пути – смещение 487.
stock const m_flLastUse = 483;           
  // (float) Время последнего использования – смещение 488.
  // _hostage_dummy_489 … _hostage_dummy_491 – зарезервировано.
stock const m_iHostageId = 487;           
  // (int) Идентификатор заложника – смещение 492.
  // Используется для передачи сообщений (gmsgHostageK, gmsgHostagePos).
  // _hostage_dummy_493, _hostage_dummy_494 – зарезервировано.
stock const m_hRescuer = 490;             
  // (указатель, pdata) Указатель на спасателя; для доступа – get_pdata_cbase_safe; смещение 495.
stock const m_iCzHostageModelIndex = 491;  
  // (int) Индекс модели заложника – смещение 496.
// _hostage_dummy_AFTER_end – зарезервировано (остальные смещения).


// CBaseEntity -> CRuleEntity
// Linux: базовое смещение +4
stock const XO_CRULEENTITY = 4;

stock const m_iszMaster = 34;  
  // (string, индекс) Имя мастера для связи с другими сущностями.


// CBaseEntity -> CRuleEntity -> CRulePointEntity
// (Не содержит pdata-полей)

// CBaseEntity -> CRuleEntity -> CRulePointEntity -> CGamePlayerEquip
const MAX_EQUIP = 32;
stock const XO_CGAMEPLAYEREQUIP = 4;

stock const m_weaponNames[MAX_EQUIP] = {35,36,...}; 
  // (массив int) Индексы для имён оружия, используйте get_pdata_string.
stock const m_weaponCount[MAX_EQUIP] = {67,68,...};
  // (массив int) Количество оружия в каждом слоте.


// CBaseEntity -> CWeaponBox
// Linux: базовое смещение +4
stock const XO_CWEAPONBOX = 4;

stock const m_rgpPlayerItems_CWeaponBox[6] = { 34 , 35 , ... };
  // (массив указателей) Предметы в коробке с оружием.
  // Пример доступа: get_pdata_cbase(weaponbox, m_rgpPlayerItems_CWeaponBox[SLOT_INDEX], XO_CWEAPONBOX)
  
stock const m_rgiszAmmo[32] = { 40 , 41 , ... };
  // (массив int) Индексы для строк имён типов патронов.
stock const m_rgAmmo_CWeaponBox[32] = { 72 , 73 , ... };
  // (массив int) Количество патронов по каждому типу.
stock const m_cAmmoTypes = 104;     
  // (int) Общее число типов патронов в коробке.
