# ////////////////OFFSET's///////////////////////
```inc
// stock const m_rgpPlayerItems_slot0 = 367
// CBaseEntity -> CBaseDelay -> CBaseAnimating
// Linux +4
stock const XO_CBASEANIMATING = 4;

stock const m_flFrameRate = 36;
stock const m_flGroundSpeed = 37;
stock const m_flLastEventCheck = 38;
stock const m_fSequenceFinished = 39;
stock const m_fSequenceLoops = 40;

// CBaseEntity -> CBaseDelay
// Linux +4
stock const XO_CBASEDELAY = 4;

stock const m_flDelay = 34;
stock const m_iszKillTarget = 35;

// CBaseEntity // LINUX DIFF 0

stock const vtbl = 0;

stock const _pev = 1;
stock const m_pGoalEnt = 2;
stock const m_pLink = 3;

stock const m_pfnThink = 4;				// linux diff 0

stock const m_pfnThink_Flag_LINUX = 5;	// linux only, diff 0

stock const m_pfnTouch = 5;				// LINUX DIFF 1

stock const m_pfnTouch_Flag_LINUX = 7;	// linux only, diff 0

stock const m_pfnUse = 6;				// LINUX DIFF 2

stock const m_pfnUse_Flag_LINUX = 9;	// linux only, diff 0

stock const m_pfnBlocked = 7;	// LINUX DIFF 3

stock const m_pfnBlocked_Flag_LINUX = 11;	// linux only, diff 0

// LINUX DIFF 4
stock const ammo_buckshot_TAB = 11;
stock const ammo_9mm_TAB = 13;
stock const ammo_556nato_TAB = 15;
stock const ammo_556natobox_TAB = 17;
stock const ammo_762nato_TAB = 19;
stock const ammo_45acp_TAB = 21;
stock const ammo_50ae_TAB = 23;
stock const ammo_338magnum_TAB = 25;
stock const ammo_57mm_TAB = 27;
stock const ammo_357sig_TAB = 29;

stock const m_flStartThrow = 30;
stock const m_flReleaseThrow = 31;
stock const m_chargeReady = 32;
stock const m_fInAttack = 33;


// CBaseEntity -> CArmoury
// Linux + 4
stock const XO_CARMOURY = 4;

stock const m_iItem = 34;		// weapon ID defined in fgd
stock const m_iCount = 35;		// item count before entity desappear
stock const m_iSaveCount = 36;		// saved m_iCount value at Spawn
stock const m_bRestoreCount = 148;		// if true [g/s]et_pdata_bool

// CBaseEntity -> CPointEntity -> CMapInfo
stock const XO_CMAPINFO = 4;

stock const m_ibuying = 34;
stock const m_flBombRadius = 35;

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster
// Linux +5
stock const XO_CBASEMONSTER = 5;

stock const m_Activity = 73;
stock const m_IdealActivity = 74;
stock const m_LastHitGroup = 75;
stock const m_bitsDamageType = 76;


// NOT OFFSETS LOL
enum {
	itbd_Paralyze,		
	itbd_NerveGas,
	itbd_Poison,
	itbd_Radiation,
	itbd_DrownRecover,
	itbd_Acid,
	itbd_SlowBurn,
	itbd_SlowFreeze,
	CDMG_TIMEBASED
}
stock const m_rgbTimeBasedDamage[CDMG_TIMEBASED] = {308,309,...};
// Usage get_pdata_char(id, m_rgbTimeBasedDamage[ itbd_DrownRecover ] )
// set_pdata_char(id, m_rgbTimeBasedDamage[ itbd_Poison ] , 200)

stock const m_MonsterState = 79;
stock const m_IdealMonsterState = 80;
stock const m_afConditions = 81;
stock const m_afMemory = 82;
stock const m_flNextAttack = 83;
stock const m_hEnemy_pent = 336;	// linux +20	// get_pdata_ent is (char) based		// 84
stock const m_hEnemy_serialnumber = 85;
stock const m_hTargetEnt_pent = 344;	// linux +20	// get_pdata_ent is (char) based		// 86
stock const m_hTargetEnt_serialnumber = 87;
stock const m_flFieldOfView = 88;
stock const m_bloodColor = 89;
stock const m_HackedGunPos[3] = {90,91,92};
stock const m_vecEnemyLKP[3] = {93,94,95};

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem
// Linux +4
stock const XO_CBASEPLAYERITEM = 4;

// CBasePlayerItem
stock const m_pPlayer = 41;
stock const m_pNext = 42;
stock const m_iId = 43;

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBasePlayerItem -> CBasePlayerWeapon
// Linux +4
stock const XO_CBASEPLAYERWEAPON = 4;

// CBasePlayerWeapon	
stock const m_iPlayEmptySound = 44;
stock const m_fFireOnEmpty = 45;  // True when the gun is empty and the player is still holding down the attack key
stock const m_flNextPrimaryAttack = 46;	// soonest time ItemPostFrame will call PrimaryAttack
stock const m_flNextSecondaryAttack = 47;	// soonest time ItemPostFrame will call SecondaryAttack
stock const m_flTimeWeaponIdle = 48;	// soonest time ItemPostFrame will call WeaponIdle
stock const m_iPrimaryAmmoType = 49;	// "primary" ammo index into players m_rgAmmo[]
stock const m_iSecondaryAmmoType = 50;	// "secondary" ammo index into players m_rgAmmo[]
stock const m_iClip = 51;	// number of shots left in the primary weapon clip, -1 it not used
stock const m_iClientClip = 52;	// the last version of m_iClip sent to hud dll
stock const m_iClientWeaponState = 53;	// the last version of the weapon state sent to hud dll (is current weapon, is on target)
stock const m_fInReload = 54;	// Are we in the middle of a reload;
stock const m_fInSpecialReload = 55;	// Are we in the middle of a reload for the shotguns
stock const m_iDefaultAmmo = 56;	// how much ammo you get when you pick up this weapon as placed by a level designer.
stock const m_iShellLate = 57;
stock const m_flWeaponSpeed = 58;	// used for guns to restore after shield
stock const m_bDelayFire = 236;

stock const m_iDirection = 60;	// The current lateral kicking direction ; 1 = right, 0 = left.

stock const m_flAccuracy = 62;
stock const m_flLastFire = 63;
stock const m_iShotFired = 64;

stock const m_iszViewModel = 68;
stock const m_flGlock18Shoot = 69;
stock const m_iGlock18ShotsFired = 70;
stock const m_flFamasShoot = 71;
stock const m_iFamasShotsFired = 72;
stock const m_flFamasBurstSpread = 73;
stock const m_fWeaponState = 74;
enum ( <<=1 )
{
	WEAPONSTATE_USP_SILENCED = 1,
	WEAPONSTATE_GLOCK18_BURST_MODE,
	WEAPONSTATE_M4A1_SILENCED,
	WEAPONSTATE_ELITE_LEFT,
	WEAPONSTATE_FAMAS_BURST_MODE,
	WEAPONSTATE_SHIELD_DRAWN
}

stock const m_flNextReload = 75;
stock const m_flDecreaseShotsFired = 76;
stock const m_usGlock18Event = 154;	// [g/s]et_pdata_short
stock const m_usFamasEvent = 155;	// [g/s]et_pdata_short

// Weapon Specific :
// Linux +4

// CC4
stock const m_bArming = 312;					// offset 82 linux		
stock const m_bInArmingAnimation = 313;	// offset 82
	// bool					_dummy_bool_330;
	// bool					_dummy_bool_331;

stock const m_flArmedTime = 79;		// offset 83 linux
	// int					_dummy_80;				// offset 84 linux // set to 1 when deployed and player has shield ?

// CHEGrenade
stock const m_usExplo_HEGREN = 156;

// CAK47
stock const m_iShell_AK47 = 78;
stock const m_fIsDeployed_AK47 = 79;
stock const m_usEvent_AK47 = 160;

// CAUG
stock const m_iShell_AUG = 78;
stock const m_fIsDeployed_AUG = 79;
stock const m_usEvent_AUG = 160;

// CDEAGLE
stock const m_iShell_DEAGLE = 78;
stock const m_usEvent_DEAGLE = 158;

// CELITE
stock const m_iShell_ELITE = 78;
stock const m_usEventLeft_ELITE = 158;
stock const m_usEventRigth_ELITE = 159;
	
// CFiveSeven
stock const m_iShell_FiveSeven = 78;
stock const m_usEvent_FiveSeven = 158;
	
// CM249
stock const m_iShell_CM249 = 78;
stock const m_fIsDeployed_CM249 = 79;
stock const m_usEvent_CM249 = 160;

// CM4A1
stock const m_iShell_M4A1 = 78;
stock const m_fIsDeployed_M4A1 = 79;
stock const m_usEvent_M4A1 = 160;

// CMAC10
stock const m_iShell_MAC10 = 78;
stock const m_fIsDeployed_MAC10 = 79;
stock const m_usEvent_MAC10 = 160;

// CMP5N
stock const m_iShell_CMP5N = 78;
stock const m_fIsDeployed_CMP5N = 79;
stock const m_usEvent_CMP5N = 160;

// CP228
stock const m_iShell_P228 = 78;
stock const m_usEvent_P228 = 158;
	
// CP90
stock const m_iShell_P90 = 78;
stock const m_fIsDeployed_P90 = 79;
stock const m_usEvent_P90 = 160;

// CSG552
stock const m_iShell_SG552 = 78;
stock const m_fIsDeployed_SG552 = 79;
stock const m_usEvent_SG552 = 160;

// CTMP
stock const m_iShell_TMP = 78;
stock const m_fIsDeployed_TMP = 79;
stock const m_usEvent_TMP = 160;

// CUMP45
stock const m_iShell_UMP45 = 78;
stock const m_fIsDeployed_UMP45 = 79;
stock const m_usEvent_UMP45 = 160;

// CUSP
stock const m_iShell_USP = 78;
stock const m_usEvent_USP = 158;
	
// CGalil
stock const m_iShell_Galil = 78;
stock const m_fIsDeployed_Galil = 79;
stock const m_usEvent_Galil = 160;

// CFamas
stock const m_iShell_Famas = 78;
stock const m_fIsDeployed_Famas = 79;
	
// CGLOCK18
stock const m_iShell_GLOCK18 = 78;
stock const m_fIsDeployed_GLOCK18 = 79;
	
// CSmokeGrenade
stock const m_usSmokeExplo = 156;
	
// CSCOUT
stock const m_iShell_SCOUT = 78;
stock const m_usEvent_SCOUT = 158;
	
// CAWP
stock const m_iShell_AWP = 78;
stock const m_usEvent_AWP = 158;
	
// CSG550
stock const m_iShell_SG550 = 78;
stock const m_usEvent_SG550 = 158;
	
// CG3SG1
stock const m_iShell_G3SG1 = 78;
stock const m_usEvent_G3SG1 = 158;
	
// CFlashbang // no private data

// CXM1014
stock const m_iShell_XM1014 = 78;
stock const m_flPumpTime_XM1014 = 79;
stock const m_usEvent_XM1014 = 160;
	
// CM3
stock const m_iShell_CM3 = 78;
stock const m_flPumpTime_CM3 = 79;
stock const m_usEvent_CM3 = 160;
	
// CC4
stock const m_bArming_C4 = 312;
stock const m_bInArmingAnimation_C4 = 313;
stock const m_flArmedTime_C4 = 79;

// CKnife
stock const m_fAllSolid_CKnife = 78;
stock const m_fStartSolid_CKnife = 79;
stock const m_fInOpen_CKnife = 80;
stock const m_fInWater_CKnife = 81;
stock const m_flFraction_CKnife = 82;
stock const m_vecEndPos_CKnife = {83,84,85};
stock const m_flPlaneDist_CKnife = 86;
stock const m_vecPlaneNormal_CKnife[3] = {87,88,89};
stock const m_pHit_CKnife = 90;
stock const m_iHitgroup_CKnife = 91;
stock const m_usEvent_CKnife = 184;
	
// CWeaponCycler
stock const m_iszModel_WeaponCycler = 78;
stock const m_iModel_WeaponCycler = 79;

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster -> CBasePlayer
// Linux +5
stock const XO_CBASEPLAYER = 5;

stock const random_seed = 96;    //    See    that    is    shared    between    client    &    server    for    shared    weapons    code
//    97    unknown
stock const m_hSpectatedTarget = 98;
stock const m_hSpectatedTarget_pent = 392;    //    linux    +20    //    m_hSpectatedTarget    takes    98    and    99    normally    //    get_pdata_ent,    no    set
stock const m_hSpectatedTarget_serialnumber = 99;
stock const m_flNextSpecButtonTime = 100;
stock const m_iSpectatedPlayerWeaponId = 101;
stock const m_fHasSpectatedPlayerC4 = 102;
stock const m_fHasSpectatedPlayerDefuseKit = 103;
stock const m_iObserverMode = 104;
stock const m_flAnimationTimeDummy = 105;
//    106    unknown
stock const m_fHasTakenHighDamage = 107;        //    Last    damage    >    60    ?
stock const m_flPainShock = 108;
stock const m_iLastZoom = 109;
stock const m_fResumeZoom = 110;
stock const m_flEjectBrass = 111;
stock const m_iKevlarType = 112;
stock const m_fHasSurvivedLastRound = 113;
stock const m_iTeam = 114;
stock const m_iAccount = 115;
stock const m_fHasPrimary = 116;
//    int        _dummy_117        //    animation    //    117    unknown
stock const m_iAnimationInCaseDie = 118;    //    set    according    to    hitplace    and    random    values    //    used    when    dies    to    set    some    properties    (velocity,    that    kind    of    stuff)
stock const m_flNextChatTime = 119;                        
//    stock const m_bHasReceivedDefItems                    120    //    (1<<0)
stock const m_bHasReceivedDefItems = 480;    //    [g/s]et_pdata_bool
stock const m_iJoinedState = 121;
stock const m_pLastCamera = 122;
stock const m_flNextCameraTime = 123;
stock const m_flLastActivityTime = 124;
//    stock const m_bools125                                        125    //    m_bHasBeenShownBriefing    (1<<0)            //    m_bHasChangeTeamThisRound    (1<<8)
stock const m_bHasBeenShownBriefing = 500;    //    [g/s]et_pdata_bool
stock const m_bHasChangeTeamThisRound = 501;    //    [g/s]et_pdata_bool
stock const m_iInternalModel = 126;
stock const m_iTeamMateKills = 127;
stock const m_iIgnoreMessages = 128;
//    stock const m_boolsNVG                                        129    //    m_bGotNVG    (1<<0)        //    m_bIsNVGSwitchedOn    (1<<8)
stock const m_bGotNVG = 516;    //    [g/s]et_pdata_bool
stock const m_bIsNVGSwitchedOn = 517;    //    [g/s]et_pdata_bool
//    BIG    HOLE
stock const m_flNextIdleCheckTime = 190;
stock const m_flNextRadioGameTime = 191;
stock const m_iRadiosLeft = 192;
//    stock const m_bools193                                        193    //    m_bHasSilentRadio    (1<<0)    //    m_bHasC4    (1<<8)    //    m_bHasDefuser    (1<<16)        //    m_bBlastDamaged    (1<<24)
stock const m_bHasSilentRadio = 772;    //    [g/s]et_pdata_bool
stock const m_bHasC4 = 773;    //    [g/s]et_pdata_bool
stock const m_bHasDefuser = 774;    //    [g/s]et_pdata_bool
stock const m_bBlastDamaged = 775;    //    [g/s]et_pdata_bool
stock const m_vecRelativeInflictorOrigin[3] = {194,195,196};
stock const m_bTruOnHeDamage = 197;                //    CBasePlayer::SetAnimation
stock const m_fNextHudTextArgsGameTime = 198;
//    LITTLE    HOLE    HudTextArgs    RELATED
stock const m_bitHudTextArgsDontResend = 204;
stock const m_iMenu = 205;
//    206    unknown
//    207    unknown
stock const m_fIsVIPRescued = 208;
//    stock const m_boolsAsEscape                                209    //    m_bHasReachRescueEscapePoint    (1<<0)    //    m_bIsVip    (1<<8)
stock const m_bHasReachRescueEscapePoint = 836;        //    [g/s]et_pdata_bool
stock const m_bIsVip = 837;        //    [g/s]et_pdata_bool
stock const m_flNextRadarUpdateTime = 210;
stock const m_vecLastPosition[3] = {211,212,213};
stock const m_iVoteKickUserId = 214;
stock const m_flNextVote = 215;    //    votemap    votekick
stock const m_iTeamKills = 216;
stock const m_iHostageKills = 217;
stock const m_iVotedMapIndex = 218;
stock const m_bIsPrimaryFireAllowed = 219;
stock const m_flLastAttackTime = 220;    //    not    sure
stock const m_flNextTeammateAttackAlert = 221;
//    stock const m_boolsKilled                                    222    //    m_bKilledByHeadShot    (1<<0)    //    m_bKilledBecauseTeamKill    (1<<8)    //    m_bNotReceivingMoneyNextRound    (1<<16)
stock const m_bKilledByHeadShot = 888;    //    [g/s]et_pdata_bool
stock const m_bKilledBecauseTeamKill = 889;    //    [g/s]et_pdata_bool
stock const m_bNotReceivingMoneyNextRound = 890;    //    [g/s]et_pdata_bool
stock const m_flNextTimeleft = 223;
stock const m_boolChangeNameAtRespawn = 896;    //    [g/s]et_pdata_bool
stock const m_szNewName = 897;    //    (224*4    +    1)    [g/s]et_pdata_string    //    char    m_szNewName[32]
stock const m_fBombDefusing = 232;    //    m_bBombDefusing    (1<<8)
stock const m_bBombDefusing = 929;
stock const m_flNextMapZoneTime = 233;
stock const m_fMapZone = 234;
stock const m_fClientMapZone = 235;
stock const m_pentBombTarget = 236;
stock const m_iPlayerSound = 237;    //    the    index    of    the    sound    list    slot    reserved    for    this    player
stock const m_iTargetVolume = 238;    //    ideal    sound    volume.    
stock const m_iWeaponVolume = 239;    //    how    loud    the    player's    weapon    is    right    now.
stock const m_iExtraSoundTypes = 240;    //    additional    classification    for    this    weapon's    sound
stock const m_iWeaponFlash = 241;    //    brightness    of    the    weapon    flash
stock const m_flStopExtraSoundTime = 242;    //    
stock const m_flFlashLightTime = 243;    //    Time    until    next    battery    draw/Recharge
stock const m_iFlashBattery = 244;    //    Flashlight    Battery    Draw
stock const m_afButtonLast = 245;
stock const m_afButtonPressed = 246;
stock const m_afButtonReleased = 247;
stock const m_pentSndLast = 248;    //    last    sound    entity    to    modify    player    room    type
stock const m_flSndRoomtype = 249;    // last roomtype set by sound entity
stock const m_flSndRange = 250;    //    dist    from    player    to    sound    entity
stock const m_flFallVelocity = 251;

// constant items
enum {
	ITEM_HEALTHKIT = 1,
	ITEM_ANTIDOTE = 2,
	ITEM_SECURITY = 3,
	ITEM_BATTERY = 4,
	MAX_ITEMS = 5	// hard coded item types
}
stock const m_rgItems[MAX_ITEMS] = { 252 , 253 , ... };

stock const m_afPhysicsFlags = 257;    //    physics    flags    -    set    when    'normal'    physics    should    be    revisited    or    overriden
stock const m_fNextSuicideTime = 258;    //    the    time    after    which    the    player    can    next    use    the    suicide    command
stock const m_flTimeStepSound = 259;    //    when    the    last    stepping    sound    was    made
stock const m_flTimeWeaponIdle_PLAYER = 260;    //    when    to    play    another    weapon    idle    animation.
stock const m_flSwimTime = 261;    //    how    long    player    has    been    underwater
stock const m_flDuckTime = 262;    //    how    long    we've    been    ducking
stock const m_flWallJumpTime = 263;    //    how    long    until    next    walljump
stock const m_flSuitUpdate = 264;    //    when    to    play    next    suit    update
stock const m_rgSuitPlayList[4] = {265,266,...};	//[CSUITPLAYLIST]    //    next    sentencenum    to    play    for    suit    update
stock const m_iSuitPlayNext = 269;    //    next    sentence    slot    for    queue    storage
stock const m_rgiSuitNoRepeat[32] = {270,271,...};    //    [32]    //[CSUITNOREPEAT]        //    suit    sentence    no    repeat    list
stock const m_rgflSuitNoRepeatTime[32] = {302,303,...};                          //    [32]    //[CSUITNOREPEAT]    //    how    long    to    wait    before    allowing    repeat
stock const m_lastDamageAmount = 334;    //    Last    damage    taken
stock const m_tbdPrev = 335;    //    Time-based    damage    timer
stock const m_flgeigerRange = 336;    //    range    to    nearest    radiation    source
stock const m_flgeigerDelay = 337;    //    delay    per    update    of    range    msg    to    client
stock const m_igeigerRangePrev = 338;
stock const m_iStepLeft = 339;    //    alternate    left/right    foot    stepping    sound
stock const m_szTextureName = 1360;    //    440*4        m_szTextureName[19    char]    //HL    13    [CBTEXTURENAMEMAX]    //    current    texture    name    we're    standing    on
stock const m_chTextureType = 1379;    //    current    texture    type    //    may    be    use    344    instead
stock const m_idrowndmg = 345;    //    track    drowning    damage    taken
stock const m_idrownrestored = 346;    //    track    drowning    damage    restored
stock const m_bitsHUDDamage = 347;    //    Damage    bits    for    the    current    fame.    These    get    sent    to    
stock const m_fInitHUD = 348;    //    True    when    deferred    HUD    restart    msg    needs    to    be    sent
stock const m_fGameHUDInitialized = 349;
stock const m_iTrain = 350;    //    Train    control    position
stock const m_fWeapon = 351;    //    Set    this    to    FALSE    to    force    a    reset    of    the    current    weapon    HUD    info
stock const m_pTank = 352;
stock const m_pTank_pent = 1408;    //    linux    +    20    //    352    //    the    tank    which    the    player    is    currently    controlling,    NULL    if    no    tank
stock const m_pTank_serialnumber = 353;    //    the    tank    which    the    player    is    currently    controlling,    NULL    if    no    tank
stock const m_fDeadTime = 354;    //    the    time    at    which    the    player    died    (used    in    PlayerDeathThink())
stock const m_fNoPlayerSound = 355;    //    a    debugging    feature.    Player    makes    no    sound    if    this    is    true.    
stock const m_fLongJump = 356;    //    does    this    player    have    the    longjump    module?
stock const m_tSneaking = 357;
stock const m_iUpdateTime = 358;    //    stores    the    number    of    frame    ticks    before    sending    HUD    update    messages
stock const m_iClientHealth = 359;    //    the    health    currently    known    by    the    client.    If    this    changes,    send    a    new
stock const m_iClientBattery = 360;    //    the    Battery    currently    known    by    the    client.    If    this    changes,    send    a    new
stock const m_iHideHUD = 361;    //    the    players    hud    weapon    info    is    to    be    hidden
stock const m_iClientHideHUD = 362;
stock const m_iFOV = 363;    //    field    of    view
stock const m_iClientFOV = 364;    //    client's    known    FOV
stock const m_iSpawnCount = 365;
stock const m_pLinkedEntity = 366;
stock const m_rgpPlayerItems_CBasePlayer[6] = { 367 , 368 , ... };
stock const m_pActiveItem = 373;
stock const m_pClientActiveItem = 374;    //    client    version    of    the    active    item
stock const m_pLastItem = 375;
stock const m_rgAmmo_CBasePlayer[32] = {376,377,...};                    //   376    //    [MAX_AMMO_SLOTS]    
stock const m_rgAmmoLast[32] = {408,409,...};    //    [MAX_AMMO_SLOTS]    
stock const m_vecAutoAim[3] = {440,441,442};
stock const m_fOnTarget = 443;
stock const m_iDeaths = 444;
stock const m_iRespawnFrames = 445;    //    used    in    PlayerDeathThink()    to    make    sure    players    can    always    respawn
// stock const m_izSBarState_0 = 446;    //    [SBAR_END]
// stock const m_izSBarState_1 = 447;    //    [SBAR_END]
// stock const m_izSBarState_2 = 448;    //    [SBAR_END]
enum sbar_data
{
	SBAR_ID_TARGETCLASS = 1,
	SBAR_ID_TARGEINDEX,
	SBAR_ID_TARGETHEALTH,
	SBAR_END,
};
stock const m_izSBarState[SBAR_END] = {446,447,448}
stock const m_flNextSBarUpdateTime = 449;
stock const m_flStatusBarDisappearDelay = 450;
stock const m_SbarString0 = 1804; // 451*4   //    [SBAR_STRING_SIZE]    //    128 char // 32
stock const m_lastx = 483;
stock const m_lasty = 484;    //    483    484    //    These    are    the    previous    update's    crosshair    angles,    DON"T    SAVE/RESTORE
stock const m_nCustomSprayFrames = 485;    //    Custom    clan    logo    frames    for    this    player
stock const m_flNextDecalTime = 486;    //    next    time    this    player    can    spray    a    decal    //    382
stock const m_szTeamName = 1948;    //    m_szTeamName[16]
stock const g_szModelIndexPlayer = 491;    //    psz
stock const m_szShieldAnimExt = 1968;    //    m_szShieldAnimExt[32]    
stock const m_iGaitSequence = 500;
stock const m_flGaitFrame = 501;
stock const m_flGaitYaw = 502;
stock const m_flPrevGaitOrigin[3] = {503,504,505};
stock const m_flPitch = 506;
stock const m_flYaw = 507;
stock const m_flGaitMovement = 508;
stock const m_fAutoWeaponSwitch = 509;
//    stock const m_boolsUserPrefsAndShield                510    //    m_bHasVGUIMenus    (1<<0)    //    m_bHasAutoHelp    (1<<8)    //    m_bUsesShield    (1<<16)    //    m_bHasShield    (1<<24)
stock const m_bHasVGUIMenus = 2040;        //    [g/s]et_pdata_bool
stock const m_bHasAutoHelp = 2041;        //    [g/s]et_pdata_bool
stock const m_bUsesShield = 2042;        //    [g/s]et_pdata_bool
stock const m_bHasShield = 2043;        //    [g/s]et_pdata_bool

// stock const m_bObserverHasTarget = 2044; // to do, make sur this offset is not a bool
stock const m_fObserverHasTarget = 511;

stock const m_flFindNextPlayerTime = 512;
stock const m_flDtMultiplier = 513;
stock const m_flFlashedUntil = 514;
stock const m_flFlashedAt = 515;
stock const m_flFlashHoldTime = 516;
stock const m_flFlashDuration = 517;
stock const m_iFlashAlpha = 518;
stock const m_flNextAutoFollowTime = 519;
stock const m_szAutoBuyData = 2080;    //    m_szAutoBuyData[256] // stock const m_szAutoBuyData[256] = {2080,2081,...} ?
stock const m_pSomeRebuyPointer = 584;    //    Rebuy    stuff    CBasePlayer::Rebuy    CBasePlayer::RebuyInitData    ClientPutInServer
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
stock const m_flNextLocationUpdateTime = 596;
stock const m_szLocation = 2388;                //    597-604    m_szLocation[28]    
stock const m_flProgressBarStartTime = 605;
stock const m_flProgressBarEndTime = 606;
// stock const m_boolsObserver607 = 607    //    m_bObserverAutoDirector    (1<<0)    //    m_bCanSwitchObserverModes    (1<<8)
stock const m_bObserverAutoDirector = 2428;    //    [g/s]et_pdata_bool
stock const m_bCanSwitchObserverModes = 2429;    //    [g/s]et_pdata_bool
//    608
stock const m_flIntense = 609;    // win    //    ?void?    CBasePlayer::Intense()    {    m_flIntense    =    gpGlobals->time    }
//    610
//    611
stock const m_flNextSayTime = 612;
stock const m_flNextSayTeamTime = 613;
stock const m_flNextFullUpdateTime = 614;
stock const m_flNextVoteTimeCmd = 615;
stock const m_flNextVoteMapTime = 616;
stock const m_flNextListMapsTime = 617;
stock const m_flNextListPlayersTime = 618;
stock const m_flNextNightVisionTime = 619; 

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle
// Linux + 4
stock const XO_CBASETOGGLE1 = 4;

stock const m_toggle_state = 41;
stock const m_flActivateFinished = 42;
stock const m_flMoveDistance = 43;
stock const m_flWait = 44;
stock const m_flLip = 45;
stock const m_flTWidth = 46;
stock const m_flTLength = 47;
stock const m_vecPosition1[3] = {48,49,50};
stock const m_vecPosition2[3] = {51,52,53};
stock const m_vecAngle1[3] = {54,55,56};
stock const m_vecAngle2[3] = {57,58,59};
stock const m_cTriggersLeft = 60;
stock const m_flHeight = 61;
stock const m_hActivator_pent = 248;		// linux + 16	// get_pdata_ent is (char) based		// 62
stock const m_hActivator_serialnumber = 63;		// 63
stock const m_pfnCallWhenMoveDone = 64;

// Linux +5
stock const XO_CBASETOGGLE2 = 5;

stock const m_pfnCallWhenMoveDone_Flag_LIN = 69;	// linux only, diff 0
stock const m_vecFinalDest[3] = {65,66,67};
stock const m_vecFinalAngle[3] = {68,69,70};
stock const m_bitsDamageInflict = 71;
stock const m_sMaster = 72;

//    CBaseEntity    ->    CBaseDelay    ->    CBaseAnimating    ->    CBaseToggle    ->    CBaseMonster    ->    CGrenade
stock const XO_CGRENADE = 5;

stock const m_bStartDefuse = 384;    //    [g/s]et_pdata_bool
stock const m_bIsC4 = 385;    //    [g/s]et_pdata_bool
stock const m_hDefuser_pent = 388;    //    LINUX    +    20                    //    offset    97
stock const m_hDefuser_serial = 98;
stock const m_flDefuseCountDown = 99;
stock const m_flC4Blow = 100;
stock const m_flNextFreqInterval = 101;
stock const m_flNextBeep = 102;
stock const m_flNextFreq = 103;
stock const m_sBeepName = 104;
stock const m_fAttenu = 105;
stock const m_flNextBlink = 106;
stock const m_flNextDefuseCheck = 107;
stock const m_bJustBlew = 108;
stock const m_iGrenadeTeam = 109;
stock const m_iC4Hurry = 110;
stock const m_pentCurBombTarget = 444;    //    LINUX    +    20    get_pdata_ent
stock const m_SGSmoke = 112;

//    int                    _dummy_113;    //    unknown    offset

stock const m_usEvent_Grenade = 228;    //    [g/s]et_pdata_short
stock const m_bLightSmoke = 458;    //    [g/s]et_pdata_bool
//    bool                    m_b459;        //    set    to    1    on    smoke    explode

stock const m_vecAngles[3] = {115,116,117};

stock const m_iRebundsCount = 118;
stock const m_fSoundSet = 119;

// CBaseEntity -> CBaseDelay -> CBaseAnimating -> CBaseToggle -> CBaseMonster -> CHostage

const XO_CHOSTAGE = 5;

stock const m_LastActivity = 96;		// Activity
stock const m_fHasGivenUseBonus = 97;
// int		_hostage_dummy_98;
stock const m_flNextFlinch = 99;	
stock const m_flNextUse = 100;		// timebased m_flNextUse
// int		_hostage_dummy_101;
// int		_hostage_dummy_102;
// int		_hostage_dummy_103;
// int		_hostage_dummy_104;
stock const m_fShouldFollow = 105;		// seems to be a flag to tell if it should follow or not
stock const m_vecOrigOrigin[3] = {106,107,108};			// RestorePos
stock const m_vecOrigAngles[3] = {109,110,111};			// RestorePos
// int		_hostage_dummy_112;
// int		_hostage_dummy_113;
// int		_hostage_dummy_114;
// int		_hostage_dummy_115_171[58];
// CBasePlayer*		_hostage_dummy_173;
stock const m_iCurPathNode = 174;					// offset 178	// CLocalNav*
stock const m_iNextPathNode = 175;					// offset 179
stock const m_flNextPathOrig2D_X = 176;			// offset 180
stock const m_flNextPathOrig2D_Y = 177;			// offset 181
// int		_hostage_dummy_178_473[298];
// EHANDLE		_hostage_dummy_476_477;
// float		_hostage_dummy_478;
stock const m_flPathCheckDelay = 479;						// offset 483
stock const m_flLastPathCheckTime = 480;			// offset 484
// int		_hostage_dummy_481;					// offset 485
stock const m_fHasReachedPathNode = 482;	// BOOL			// offset 487
stock const m_flLastUse = 483;								// offset 488
// int		_hostage_dummy_489;
// int		_hostage_dummy_490;
// int		_hostage_dummy_491;
stock const m_iHostageId = 487;						// offset 492					// gmsgHostageK, gmsgHostagePos pfnWriteByte(this->_hostage_dummy_391);
// BOOL		_hostage_dummy_493;
// int		_hostage_dummy_494;
stock const m_hRescuer = 490;							// offset 495		// seems to be CHostageImprov*
stock const m_iCzHostageModelIndex = 491;		// offset 496
// int		_hostage_dummy_AFTER_end[5000];

// monster size 404 dword
// total size 1420 dword
// offsets size: 1016

// CBaseEntity -> CRuleEntity
// Linux +4
stock const XO_CRULEENTITY = 4;

stock const m_iszMaster = 34;


//  CBaseEntity -> CRuleEntity -> CRulePointEntity
// No Pdatas

//  CBaseEntity -> CRuleEntity -> CRulePointEntity -> CGamePlayerEquip
const MAX_EQUIP = 32

stock const XO_CGAMEPLAYEREQUIP = 4;

stock const m_weaponNames[MAX_EQUIP] = {35,36,...};
stock const m_weaponCount[MAX_EQUIP] = {67,68,...};

// CBaseEntity -> CWeaponBox
// Linux +4
stock const XO_CWEAPONBOX = 4;

stock const m_rgpPlayerItems_CWeaponBox[6] = { 34 , 35 , ... };
// Usage get_pdata_cbase(weaponbox, m_rgpPlayerItems_CWeaponBox[ SLOT_INDEX ], XO_CWEAPONBOX)

stock const m_rgiszAmmo[32] = { 40 , 41 , ... };
stock const m_rgAmmo_CWeaponBox[32] = { 72 , 73 , ... };
stock const m_cAmmoTypes = 104;
