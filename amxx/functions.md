# /* Действительно ли человек из STEAM */
```pawn
#if AMXX_VERSION_NUM < 183
stock bool:is_user_steam(id)
{
	// Author Sh0oter
	new dp_pointer = get_cvar_pointer("dp_r_id_provider");
	if(dp_pointer)
	{
		server_cmd("dp_clientinfo %d", id);
		server_exec();
		return (get_pcvar_num(dp_pointer) == 2) ? true : false
	}
	return false
}
#endif
```

# /* ID ентити оружия в руках у игрока */
```pawn
stock get_player_active_item_id(const player_id)
{
	if(!is_user_connected(player_id)) return 0;

	new weapon_id = get_pdata_cbase(player_id, m_pActiveItem);

	return (pev_valid(weapon_id) == 2 ? weapon_id : 0);
}
```

# /* CSW ID ентити оружия в руках у игрока */
```pawn
stock get_player_active_csw_id(const player_id)
{
	if(!is_user_connected(player_id)) return 0;
	
	new weapon_id = get_pdata_cbase(player_id, m_pActiveItem);

	return (pev_valid(weapon_id) == 2 ? cs_get_weapon_id(weapon_id) : 0);
}
```

# /*  Анимация модели игрока */
```pawn
stock set_player_animation(const player_id, const anim_name[])
{
	static anim_id, Float:flFrameRate, bool:bLoops, Float:flGroundSpeed;

	if ((anim_id = lookup_sequence(player_id, anim_name, flFrameRate, bLoops, flGroundSpeed)) == -1)
		anim_id = 0;

	static Float:gametime;

	gametime = halflife_time();
	
	set_pev(player_id, pev_frame, 0.0);
	set_pev(player_id, pev_framerate, 1.0);
	set_pev(player_id, pev_animtime, gametime );
	set_pev(player_id, pev_sequence, anim_id);
	
	set_pdata_int(player_id, 40, bLoops, 4);
	set_pdata_int(player_id, 39, 0, 4);

	set_pdata_float(player_id, 36, flFrameRate, 4);
	set_pdata_float(player_id, 37, flGroundSpeed, 4);
	set_pdata_float(player_id, 38, gametime , 4);

	set_pdata_int(player_id, 73, 28, 5);
	set_pdata_int(player_id, 74, 28, 5);   
	set_pdata_float(player_id, 220, gametime , 5);
}

/* Воспроизвети анимацию от первого лица (с ID звуков И внешней анимацией) */
stock play_weapon_animation(const player_id, const sequence_id = 0, const body_id = 0)
{
	set_pev(player_id, pev_weaponanim, sequence_id);
	
	message_begin(MSG_ONE, SVC_WEAPONANIM, _, player_id);
	{
		write_byte(sequence_id);
		write_byte(body_id);
	}
	message_end();
}

/* Закрыть открытое меню */
stock show_empty_menu(const player_id)
{
	menu_cancel(player_id);
	show_menu(player_id, 0, "^n", 1);
}

/* Цветной чат с colorchat.inc */
/* !rКрасный !gЗелёный !bСиний !wБелый !yЖелтый (!rКрасный/!bСиний/!wБелый только 1 можно использовать в одном сообщении -_-) */
stock color_print(player_id = 0, message[], any:...)
{
	new color_id, buffer2[768];

	vformat(buffer2, 191, message, 3);

	if(contain(buffer2, "!r") != -1)
		color_id = -2;
	else if(contain(buffer2, "!b") != -1)
		color_id = -3;
	else if(contain(buffer2, "!w") != -1)
		color_id = -1;
	else
		color_id = 0;

	replace_all(buffer2, 191, "!y", "^1");
	replace_all(buffer2, 191, "!g", "^4");
	
	replace_all(buffer2, 191, "!r", "^3");
	replace_all(buffer2, 191, "!w", "^3");
	replace_all(buffer2, 191, "!b", "^3");

	client_print_color(player_id, color_id, "%s", buffer2);
}


stock remove_color_tag(output[], const len = 191, const mode = 0)
{
	replace_all(output, len, "!y", (mode ? "" : "y"));
	replace_all(output, len, "!g", (mode ? "" : "g"));
	
	replace_all(output, len, "!r", (mode ? "" : "r"));
	replace_all(output, len, "!w", (mode ? "" : "w"));
	replace_all(output, len, "!b", (mode ? "" : "b"));
	
	if(mode)
	{
		replace_all(output, len, "]", "");
		replace_all(output, len, "[", "");
	}
}

/* Показ/скрытие иконки у ирока */
stock set_status_icon(const player_id, const icon[], const colors[3], const status = 0)
{
	static msg_status_icon_id;

	if(!msg_status_icon_id)
		msg_status_icon_id = get_user_msgid("StatusIcon");

	message_begin(MSG_ONE_UNRELIABLE, msg_status_icon_id, {0,0,0}, player_id);
	{
		write_byte(status);
		write_string(icon);
		write_byte(colors[0]);
		write_byte(colors[1]);
		write_byte(colors[2]);
	}
	message_end();
}

/* Возвращает ID массива склонения */
stock decline_of_num(number)
{  
	new cases[] = {2, 0, 1, 1, 1, 2};  
	return (number % 100 > 4 && number % 100 < 20 ) ? 2 : cases[ min(number % 10, 5) ] ;  
}

/* Находится ли игрок сзади другого объекта */
stock get_center(const entity_id, Float: vector_output[3])
{
	new 
		Float:entity_absmax[3];
		Float:entity_absmin[3];

	pev(iEntity, pev_absmax, entity_absmax);
	pev(iEntity, pev_absmin, entity_absmin);

	xs_vec_add(entity_absmax, entity_absmin, vector_output);
	xs_vec_mul_scalar(vector_output, 0.5, vector_output);
}
stock get_player_look_on_back(const player_id, const victim_id)
{
	new 
		Float:vector_center[3],
		Float:entity_angles[3],
		Float:vector_forward[3],
		Float:attack_direction[3];

	get_center(victim_id, vector_center);
	get_center(player_id, attack_direction);

	xs_vec_sub(attack_direction, vector_center, attack_direction);
	xs_vec_normalize(attack_direction, attack_direction);

	pev(victim_id, pev_angles, entity_angles);

	engfunc(EngFunc_MakeVectors, entity_angles);
	global_get(glb_v_forward, vector_forward);
	
	xs_vec_mul_scalar(attack_direction, -1.0, attack_direction);

	return (xs_vec_dot(vector_forward, attack_direction) > 0.3);
}
/* <= */

stock create_velocity(Float:origin_start[3], Float:origin_end[3], Float:speed, Float:output[3])
{
	static Float:length;

	output[0] = origin_end[0] - origin_start[0] 
	output[1] = origin_end[1] - origin_start[1] 
	output[2] = origin_end[2] - origin_start[2] 
	
	length = vector_length(output);

	output[0] = output[0] * speed / length;
	output[1] = output[1] * speed / length;
	output[2] = output[2] * speed / length;
}

stock get_weapon_owner_id(const entity_id)
{
	if(pev_valid(entity_id) == 2)
		return get_pdata_cbase_safe(entity_id, 41, 4);
	return 0;
}

stock precache_weaponlist(const classname[])
{
	new pathtxt[64], pathspr[64]; 
	new filedata[128], sprname[48], temp[1];

	format(pathtxt, charsmax(pathtxt), "sprites/%s.txt", classname);
	engfunc(EngFunc_PrecacheGeneric, pathtxt);

	new file = fopen(pathtxt, "rb");
	while(file && !feof(file)) 
	{
		fgets(file, filedata, charsmax(filedata));
		trim(filedata);

		if(!strlen(filedata)) 
			continue;

		new pos = containi(filedata, "640");	
			
		if(pos == -1)
			continue;
			
		format(filedata, charsmax(filedata), "%s", filedata[pos+3]);		
		trim(filedata);

		strtok(filedata, sprname, charsmax(sprname), temp, charsmax(temp), ' ', 1);
		trim(sprname);
		
		format(pathspr, charsmax(pathspr), "sprites/%s.spr", sprname);
		
		if(!file_exists(pathspr)) continue;
		
		engfunc(EngFunc_PrecacheGeneric, pathspr);
	}

	if(file) fclose(file);
}

stock get_player_dist_aim_entity(const player_id, const distance)
{
	new 
		entity_id = -1,

		Float:direction[3],
		Float:eyes_origin[3],
		Float:position_end[3],
		Float:player_angles[3],

		Float:tr_fraction_fl;
	
	
	// Получаем углы игрока
	pev(player_id, pev_angles, player_angles);
	
	// Получам положение глаз и переводим во float
	pev(player_id, pev_origin, eyes_origin);
	pev(player_id, pev_view_ofs, direction);
	xs_vec_add(eyes_origin, direction, eyes_origin);
	
	// Высчитываем координаты прицела
	pev(player_id, pev_v_angle, direction);
	engfunc(EngFunc_MakeVectors, direction);
	global_get(glb_v_forward, direction);
	
	// Проводим линию от прицела
	xs_vec_mul_scalar(direction, float(distance), direction);
	
	// Получаем координаты точки сопрекосновения
	xs_vec_add(eyes_origin, direction, position_end);
	
	engfunc(EngFunc_TraceLine, eyes_origin, position_end, DONT_IGNORE_MONSTERS, player_id, 0);
	{
		get_tr2(0, TR_flFraction, tr_fraction_fl);

		// Если коснулись чего-то
		if (tr_fraction_fl < 1.0)
			entity_id = get_tr2(0, TR_Hit);
	}

	return entity_id;
}

stock get_primary_csw_id(const player_id)
{
	new entity_id = get_pdata_cbase(player_id, 367 + 1);
	
	if(pev_valid(entity_id))
		return cs_get_weapon_id(entity_id);
	return 0;
}

stock give_item_csw(const player_id, const csw_id)
{
	new weapon_classname[32];
	get_weaponname(csw_id, weapon_classname, 31);

	give_item(player_id, weapon_classname);
	cs_set_user_bpammo(player_id, csw_id, WEAPON_MAXBPAMMO[csw_id]);
}

stock player_reset_maxspeed(const player_id)
{
	if(!is_user_alive(player_id)) return;

	new active_item_id = get_pdata_cbase(player_id, m_pActiveItem);

	if(pev_valid(active_item_id) == 2)
	{
		new Float:item_maxspeed;

		ExecuteHam(Ham_CS_Item_GetMaxSpeed, active_item_id, item_maxspeed);
		if(item_maxspeed > 1.0)
			set_user_maxspeed(player_id, item_maxspeed);
	}
}

stock arrayset_float(Float:array[], Float:value, size)
{
	for(new i; i < size; i++)
		array[i] = value;
}

// Checks if a space is vacant (credits to VEN)
stock is_hull_vacant(Float:origin[3], hull)
{
	engfunc(EngFunc_TraceHull, origin, origin, 0, hull, 0, 0)
	
	if (!get_tr2(0, TR_StartSolid) && !get_tr2(0, TR_AllSolid) && get_tr2(0, TR_InOpen))
		return true;
	
	return false;
}

stock get_aim_florigin(const player_id, Float:position_end[3], Float:vecplanenormal[3])
{
	new Float:player_angles[3], Float:eyes_origin[3], Float:direction[3];
	
	// Получаем углы игрока
	pev(player_id, pev_angles, player_angles);
	
	// Получам положение глаз и переводим во float
	pev(player_id, pev_origin, eyes_origin);
	pev(player_id, pev_view_ofs, direction);
	xs_vec_add(eyes_origin, direction, eyes_origin);
	
	// Высчитываем координаты прицела
	pev(player_id, pev_v_angle, direction);
	engfunc(EngFunc_MakeVectors, direction);
	global_get(glb_v_forward, direction);
	
	// Проводим линию от прицела до стены
	xs_vec_mul_scalar(direction, 9999.0, direction);
	xs_vec_add(eyes_origin, direction, position_end);

	// Получаем координаты точки соприкосновения
	engfunc(EngFunc_TraceLine, eyes_origin, position_end, IGNORE_MONSTERS, player_id, 0);
	get_tr2(0, TR_vecEndPos, position_end);
	get_tr2(0, TR_vecPlaneNormal, vecplanenormal);
	
	return 1;
}

stock player_update_hud(const player_id, const csw_id)
{
	// This set clip ammo
	message_begin(MSG_ONE_UNRELIABLE, get_user_msgid("CurWeapon"), _, player_id);
	write_byte(true);
	write_byte(csw_id);
	write_byte(cs_get_weapon_ammo(get_pdata_cbase(player_id, m_pActiveItem)))
	message_end()
	
	// This set BP ammo
	message_begin(MSG_ONE_UNRELIABLE, get_user_msgid("AmmoX"), _, player_id);
	write_byte(csw_id);
	write_byte(cs_get_user_bpammo(player_id, csw_id));
	message_end();	
}

/* Первая буква будет заглавной */
stock str_set_capital_letter(str[], const len)
{
	new first_chars[2];
	
	formatex(first_chars, 1, "%.1s", str);
	
	replace(str, len, first_chars, "");
	
	strtoupper(first_chars);
	
	format(str, len, "%s%s", first_chars, str);
}

stock set_user_recoil(const id, Float:recoil)
{
    new Float: push[3];
    pev (id, pev_punchangle, push);
    xs_vec_mul_scalar (push, recoil, push);
    set_pev (id, pev_punchangle, push);
}

stock is_user_spectating(const player_id)
{
	new spectr_id;

	switch(pev(player_id, pev_iuser1))
	{
		case 1, 2, 4: spectr_id = pev(player_id, pev_iuser2)

		default: return 0;
	}

	return spectr_id;
}

/* Получить ID ентити у игрока по CSW_ID */
stock get_entity_from_csw_id(const player_id, const csw_id)
{
	const m_pNext = 42
	const m_rgpPlayerItems_slot0 = 367
	
	new slot = 1;
	new entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);

	if(!pev_valid(entity_id))
	{
		for(; slot < 4; slot++)
		{
			entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);
			if(pev_valid(entity_id))
				break;
		}
	}
	
	while(pev_valid(entity_id))
	{
		if(cs_get_weapon_id(entity_id) == csw_id)
			return entity_id;

		entity_id = get_pdata_cbase(entity_id, m_pNext, 4);
		
		if(slot < 4 && !pev_valid(entity_id))
		{
			slot++;
			entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);
		}
	}

	return 0;
}

stock get_weaponbox_csw_id(const entity_id) 
{ 
	new csw_id, m_rgpPlayerItems_CWeaponBox[6] = {34,35,...};
	for(new i; i < 5; i++) 
	{ 
		csw_id = get_pdata_cbase(entity_id, m_rgpPlayerItems_CWeaponBox[i], 4);
		if(csw_id > 0) 
			return cs_get_weapon_id(csw_id)
	} 

	return 0
}

stock get_weaponbox_id(const entity_id) 
{ 
	new weaponbox_id = -1, find_entity_id, m_rgpPlayerItems_CWeaponBox[6] = {34,35,...};
	while((weaponbox_id = find_ent_by_class(weaponbox_id, "weaponbox")))
	{
		if(is_valid_ent(weaponbox_id))
		{
			for(new i; i < 5; i++) 
			{ 
				find_entity_id = get_pdata_cbase(weaponbox_id, m_rgpPlayerItems_CWeaponBox[i], 4);
				if(find_entity_id > 0 && find_entity_id == entity_id) 
					return weaponbox_id;
			}
		}
	}

	return 0;
}

/* Закрыть меню закупки VGUI */
stock close_vgui_buy(const player_id)
{
	message_begin(MSG_ONE, get_user_msgid("BuyClose"), _, player_id);
	message_end();
}


stock effect_screen_fade( const player_id = 0, const duration = 0, const holdtime = 0, const fadetype = 0, const colors[3] = {0,0,0}, const alpha = 255 )
{
	message_begin( ( player_id ? MSG_ONE : MSG_ALL ), get_user_msgid("ScreenFade"), {0,0,0}, player_id);
	{
		write_short(duration<<12);
		write_short(holdtime<<16);
		write_short(fadetype<<1);
		write_byte(colors[0]); //r
		write_byte(colors[1]);   //g
		write_byte(colors[2]);   //b
		write_byte(alpha); //alpha
	}
	message_end();
}

/* стандартный эффект попадания от высрела (всё кроме дымки) */
stock effect_multigunshot(const Float:hit_origin[3], const Float:direction[3], const x_noise = 0, const y_noise = 0, const count = 1)
{
	new decalindex = engfunc(EngFunc_DecalIndex, DECAL_LIST[random(DECAL_LIST_CHARSMAX)]);
	
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_MULTIGUNSHOT);
		engfunc(EngFunc_WriteCoord,hit_origin[0]);
		engfunc(EngFunc_WriteCoord,hit_origin[1]);
		engfunc(EngFunc_WriteCoord,hit_origin[2]);
		engfunc(EngFunc_WriteCoord,direction[0]);
		engfunc(EngFunc_WriteCoord,direction[1]);
		engfunc(EngFunc_WriteCoord,direction[2]);
		write_coord(x_noise);
		write_coord(y_noise);
		write_byte(count);
		write_byte(decalindex);
	}
	message_end();
}

/**
 * Creates an explosion which the engine will do.
 * Note: Requieres Engine Module. Tested only on CS Steam.
 *
 * @param vecCenter                Position where the explosion should be created.
 * @param iMagnitude            Explosion size. This defines the explosion's max damage and sprite's size
 * @param iFlags                Explosion flags which you can find on hlsdk_const.inc
 * @param bDoDamage                Should do a radius damage?
 * @param bNoActivate        If this param is on true, the entity won't explode, and you must do it manually
 * @return                    Entity index on sucess, 0 on failure.
 */
//https://forums.alliedmods.net/showthread.php?t=142412
stock effect_origin_explosion_def(const Float:vecCenter[3], iMagnitude, iFlags = 0, bool:bDoDamage = false, bool:bNoActivate = false)
{
    // env_explosion entities are the ones
    // who creates explosions. So we are available
    // for using this one for creating explosions
    new iEnt = create_entity("env_explosion")
    
    if(!iEnt) // Invalid?
    return 0
    
    // The center of the explosion
    entity_set_origin(iEnt, vecCenter)
    
    // Key values must be only string, so we must
    // convert it from Int to String.
    new szMagnitude[8] // In explode.cpp the char size is 128 lol.
    formatex(szMagnitude, charsmax(szMagnitude), "%3d", iMagnitude) // Exactly as how the engine does it.
    DispatchKeyValue(iEnt, "iMagnitude", szMagnitude) // This will modify m_iMagnitude value.
    
    // In hlsdk_const.inc you can find env_explosion's spawn flags
    if(!bDoDamage) // If you don't want to create a damage sphere, so we must only add this flag.
        entity_set_int(iEnt, EV_INT_spawnflags, iFlags | SF_ENVEXPLOSION_NODAMAGE)
    else
        entity_set_int(iEnt, EV_INT_spawnflags, iFlags) // 3rd parameter flags.
    
    // Applying some pev values, and offset modifications
    DispatchSpawn(iEnt)
    
    if(!bNoActivate)
        force_use(iEnt, iEnt) // The engine doesn't care who used it. Btw, this actives the explosion.
    
    return iEnt
}
stock effect_entity_explosion_def(const entity_id, iMagnitude, iFlags = 0, bool:bDoDamage = false, bool:bNoActivate = false)
{
    new Float:vecCenter[3]; pev(entity_id, pev_origin, vecCenter);
	// env_explosion entities are the ones
    // who creates explosions. So we are available
    // for using this one for creating explosions
    new iEnt = create_entity("env_explosion")
    
    if(!iEnt) // Invalid?
    return 0
    
    // The center of the explosion
    entity_set_origin(iEnt, vecCenter)
    
    // Key values must be only string, so we must
    // convert it from Int to String.
    new szMagnitude[8] // In explode.cpp the char size is 128 lol.
    formatex(szMagnitude, charsmax(szMagnitude), "%3d", iMagnitude) // Exactly as how the engine does it.
    DispatchKeyValue(iEnt, "iMagnitude", szMagnitude) // This will modify m_iMagnitude value.
    
    // In hlsdk_const.inc you can find env_explosion's spawn flags
    if(!bDoDamage) // If you don't want to create a damage sphere, so we must only add this flag.
        entity_set_int(iEnt, EV_INT_spawnflags, iFlags | SF_ENVEXPLOSION_NODAMAGE)
    else
        entity_set_int(iEnt, EV_INT_spawnflags, iFlags) // 3rd parameter flags.
    
    // Applying some pev values, and offset modifications
    DispatchSpawn(iEnt)
    
    if(!bNoActivate)
        force_use(iEnt, iEnt) // The engine doesn't care who used it. Btw, this actives the explosion.
    
    return iEnt
}


stock effect_entity_explosion(const entity_id, const spite_id, const soundpath[] = "", const spite_id_second = 0, const scale = 10, const framerate = 30)
{
	new Float:origin[3], TE_FLAG; pev(entity_id, pev_origin, origin);

	TE_FLAG |= TE_EXPLFLAG_NODLIGHTS;
	TE_FLAG |= TE_EXPLFLAG_NOSOUND;
	TE_FLAG |= TE_EXPLFLAG_NOPARTICLES;
	
	engfunc(EngFunc_MessageBegin, MSG_PAS, SVC_TEMPENTITY, origin);
	{
		write_byte(TE_EXPLOSION);
		engfunc(EngFunc_WriteCoord, origin[0]);
		engfunc(EngFunc_WriteCoord, origin[1]);
		engfunc(EngFunc_WriteCoord, origin[2] + 36.0);
		write_short(spite_id);
		write_byte(scale);
		write_byte(framerate);
		write_byte(TE_FLAG);
	}
	message_end();
	
	if(spite_id_second)
	{
		engfunc(EngFunc_MessageBegin, MSG_PAS, SVC_TEMPENTITY, origin);
		{
			write_byte(TE_EXPLOSION);
			engfunc(EngFunc_WriteCoord, origin[0]);
			engfunc(EngFunc_WriteCoord, origin[1]);
			engfunc(EngFunc_WriteCoord, origin[2] + 36.0);
			write_short(spite_id_second);
			write_byte(scale);
			write_byte(framerate);
			write_byte(TE_FLAG);
		}
		message_end();
	}
	
	if(soundpath[0])
		emit_sound(entity_id, CHAN_BODY, soundpath, VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
	
	// engfunc(EngFunc_RemoveEntity, entity_id);
}

stock effect_entity_blast(const entity_id, const sprite_id, const color[3] = {200, 0, 0}, const soundpath[] = "")
{
	new Float:originF[3]; pev(entity_id, pev_origin, originF);
	
	// Smallest ring
	engfunc(EngFunc_MessageBegin, MSG_PVS, SVC_TEMPENTITY, originF, 0)
	write_byte(TE_BEAMCYLINDER) // TE id
	engfunc(EngFunc_WriteCoord, originF[0]) // x
	engfunc(EngFunc_WriteCoord, originF[1]) // y
	engfunc(EngFunc_WriteCoord, originF[2]) // z
	engfunc(EngFunc_WriteCoord, originF[0]) // x axis
	engfunc(EngFunc_WriteCoord, originF[1]) // y axis
	engfunc(EngFunc_WriteCoord, originF[2]+385.0) // z axis
	write_short(sprite_id) // sprite
	write_byte(0) // startframe
	write_byte(0) // framerate
	write_byte(4) // life
	write_byte(60) // width
	write_byte(0) // noise
	write_byte(color[0]) // red
	write_byte(color[1]) // green
	write_byte(color[2]) // blue
	write_byte(200) // brightness
	write_byte(0) // speed
	message_end()
	
	// Medium ring
	engfunc(EngFunc_MessageBegin, MSG_PVS, SVC_TEMPENTITY, originF, 0)
	write_byte(TE_BEAMCYLINDER) // TE id
	engfunc(EngFunc_WriteCoord, originF[0]) // x
	engfunc(EngFunc_WriteCoord, originF[1]) // y
	engfunc(EngFunc_WriteCoord, originF[2]) // z
	engfunc(EngFunc_WriteCoord, originF[0]) // x axis
	engfunc(EngFunc_WriteCoord, originF[1]) // y axis
	engfunc(EngFunc_WriteCoord, originF[2]+470.0) // z axis
	write_short(sprite_id) // sprite
	write_byte(0) // startframe
	write_byte(0) // framerate
	write_byte(4) // life
	write_byte(60) // width
	write_byte(0) // noise
	write_byte(color[0]) // red
	write_byte(color[1]) // green
	write_byte(color[2]) // blue
	write_byte(200) // brightness
	write_byte(0) // speed
	message_end()
	
	// Largest ring
	engfunc(EngFunc_MessageBegin, MSG_PVS, SVC_TEMPENTITY, originF, 0)
	write_byte(TE_BEAMCYLINDER) // TE id
	engfunc(EngFunc_WriteCoord, originF[0]) // x
	engfunc(EngFunc_WriteCoord, originF[1]) // y
	engfunc(EngFunc_WriteCoord, originF[2]) // z
	engfunc(EngFunc_WriteCoord, originF[0]) // x axis
	engfunc(EngFunc_WriteCoord, originF[1]) // y axis
	engfunc(EngFunc_WriteCoord, originF[2]+555.0) // z axis
	write_short(sprite_id) // sprite
	write_byte(0) // startframe
	write_byte(0) // framerate
	write_byte(4) // life
	write_byte(60) // width
	write_byte(0) // noise
	write_byte(color[0]) // red
	write_byte(color[1]) // green
	write_byte(color[2]) // blue
	write_byte(200) // brightness
	write_byte(0) // speed
	message_end()
	
	if(soundpath[0])
		emit_sound(entity_id, CHAN_BODY, soundpath, VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
}

stock effect_origin_show_sprite(const Float:origin[3], const sprite_id, const size = 5)
{
	engfunc(EngFunc_MessageBegin, MSG_BROADCAST, SVC_TEMPENTITY, origin, 0)
	{
		write_byte(TE_SPRITE);
		engfunc(EngFunc_WriteCoord, origin[0])//х - координата
		engfunc(EngFunc_WriteCoord, origin[1])//у - координата
		engfunc(EngFunc_WriteCoord, origin[2])//z - координата
		write_short(sprite_id)// id спрайта
		write_byte(size) //масштаб
		write_byte(100)//яркость
	}
	message_end();
}

stock effect_origin_particleburst(const Float:origin[3], const radius = 10, const color_num = 108, const duration = 10)
{
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_PARTICLEBURST)
		write_coord_f(origin[0])
		write_coord_f(origin[1])
		write_coord_f(origin[2])
		write_short(radius) // radius
		write_byte(color_num) // color
		write_byte(duration) // duration (will be randomized a bit)
	}
	message_end();
}

stock effect_origin_lavasplash(const Float:origin[3])
{
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_LAVASPLASH)
		write_coord_f(origin[0])
		write_coord_f(origin[1])
		write_coord_f(origin[2])
	}
	message_end();
}

stock effect_origin_tp_quake1(const Float:origin[3])
{
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_TELEPORT)
		write_coord_f(origin[0])
		write_coord_f(origin[1])
		write_coord_f(origin[2])
	}
	message_end();
}

stock effect_entity_tp_quake1(const entity_id)
{
	new Float:origin[3]; pev(entity_id, pev_origin, origin);
	
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_TELEPORT)
		write_coord_f(origin[0])
		write_coord_f(origin[1])
		write_coord_f(origin[2])
	}
	message_end();
}

stock effect_origin_implosion(const Float:origin[3], const radius = 30, const count = 10, const life = 20)
{
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_IMPLOSION)
		write_coord_f(origin[0])
		write_coord_f(origin[1])
		write_coord_f(origin[2])
		write_byte(radius)
		write_byte(count)
		write_byte(life)
	}
	message_end();
}

stock effect_origin_fireworks(const Float:origin[3], const sprite_id, const soundpath[] = "")
{
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_SPRITE);
		engfunc(EngFunc_WriteCoord, origin[0]+random_float(-5.0, 5.0));
		engfunc(EngFunc_WriteCoord, origin[1]+random_float(-5.0, 5.0));
		engfunc(EngFunc_WriteCoord, origin[2]+200.0);
		write_short(sprite_id);
		write_byte(15);
		write_byte(200);
	}
	message_end();
}

stock effect_entity_fireworks(const entity_id, const sprite_id, const soundpath[] = "")
{
	new Float:origin[3]; pev(entity_id, pev_origin, origin);
	
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_SPRITE);
		engfunc(EngFunc_WriteCoord, origin[0]+random_float(-5.0, 5.0));
		engfunc(EngFunc_WriteCoord, origin[1]+random_float(-5.0, 5.0));
		engfunc(EngFunc_WriteCoord, origin[2]+200.0);
		write_short(sprite_id);
		write_byte(15);
		write_byte(200);
	}
	message_end();
	
	if(soundpath[0])
		emit_sound(entity_id, CHAN_BODY, soundpath, VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
}

stock effect_entity_make_shots(const entity_id, const target_id, const soundpath[] = "")
{
	new Float:origin_start[3], Float:origin_end[3];
	
	pev(entity_id, pev_origin, origin_start);
	pev(target_id, pev_origin, origin_end);
	
	message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
	{
		write_byte(TE_TRACER);
		write_coord_f(origin_start[0]);
		write_coord_f(origin_start[1]);
		write_coord_f(origin_start[2]);
		write_coord_f(origin_end[0]);
		write_coord_f(origin_end[1]);
		write_coord_f(origin_end[2]);
	}
	message_end();
	
	if(soundpath[0])
		emit_sound(entity_id, CHAN_BODY, soundpath, VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
}

stock bool:is_playable_team(const CsTeams:team)
	return (team == CS_TEAM_CT || team == CS_TEAM_T ? true : false);
	
stock set_player_join_team(const player_id, const team_id)
{
	new buffer2[512];
	
	formatex(buffer2, charsmax(buffer2), "jointeam %d", team_id);
	
	client_cmd(player_id, buffer2);
	engclient_cmd(player_id, buffer2);
	client_cmd(player_id, "joinclass 5");
	engclient_cmd(player_id, "joinclass 5");

	
	client_cmd(player_id, "menuselect5;");
	engclient_cmd(player_id, "menuselect5;");
	
	menu_cancel(player_id);
	show_menu(player_id, 0, "^n", 1);
	
	message_begin(MSG_ONE, get_user_msgid("BuyClose"), _, player_id);
	message_end();
}

stock CsTeams:get_player_team(const player_id)
{
	return (is_user_connected(player_id) ? cs_get_user_team(player_id) : CS_TEAM_UNASSIGNED);
}

stock CsTeams:get_enemy_team(const player_id = 0, CsTeams:team = CS_TEAM_UNASSIGNED)
{
	if(player_id)
		return (get_player_team(player_id) != CS_TEAM_CT ? CS_TEAM_CT : CS_TEAM_T);
	else
		return (team != CS_TEAM_CT ? CS_TEAM_CT : CS_TEAM_T);
	return CS_TEAM_UNASSIGNED;
}

stock get_weapon_in_slot(const player_id, const slot)
{
	new entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);
	new classname[33];
	while(pev_valid(entity_id))
	{
		pev(entity_id, pev_classname, classname, 32);

		entity_id = get_pdata_cbase(entity_id, m_pNext, 4);
		
		//ham_strip_weapon(player_id, classname);

		// fm_strip_user_gun(player_id, get_weaponid(classname));
		return get_weaponid(classname);
	}
	return 0;
}

stock strip_weapons_in_slot(const player_id, const slot)
{
	new entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);
	new classname[33];
	while(pev_valid(entity_id))
	{
		pev(entity_id, pev_classname, classname, 32);

		entity_id = get_pdata_cbase(entity_id, m_pNext, 4);
		
		//ham_strip_weapon(player_id, classname);

		fm_strip_user_gun(player_id, get_weaponid(classname));
	}
}

stock drop_weapons_in_slot(const player_id, const slot)
{
	new entity_id = get_pdata_cbase(player_id, m_rgpPlayerItems_slot0 + slot);
	new classname[33];
	while(pev_valid(entity_id))
	{
		pev(entity_id, pev_classname, classname, 32);

		entity_id = get_pdata_cbase(entity_id, m_pNext, 4);

		engclient_cmd(player_id, "drop", classname);
	}
}

/* Удаляем weaponbox и связующую ентити */
stock bool:del_weaponbox_right(const weaponbox_id)
{
	if(!is_valid_ent(weaponbox_id)) return false;

	new 
		max_entityes = get_global_int(GL_maxEntities),
		find_ent_id = 1, 
		owner_id;
	
	for(; find_ent_id < max_entityes; find_ent_id++)
	{
		if(!is_valid_ent(find_ent_id)) continue;
		
		owner_id = entity_get_edict(find_ent_id, EV_ENT_owner);
		if(!(0 < owner_id < 33) && owner_id == weaponbox_id)
		{
			remove_entity(find_ent_id);
			break;
		}
	}
	
	remove_entity(weaponbox_id);
	
	return true;
}

/* Возвращает classname оружия и entity_id по weaponbox_id */
stock get_weaponbox_info(const weaponbox_id, classname[] = "")
{
	if(!is_valid_ent(weaponbox_id)) return 0;

	new 
		max_entityes = get_global_int(GL_maxEntities),
		find_ent_id = 1, 
		owner_id;
	
	for(; find_ent_id < max_entityes; find_ent_id++)
	{
		if(!is_valid_ent(find_ent_id)) continue;
		
		owner_id = entity_get_edict(find_ent_id, EV_ENT_owner);
		if(!(0 < owner_id < 33) && owner_id == weaponbox_id)
		{
			get_weaponname(cs_get_weapon_id(find_ent_id), classname, 32);
			return find_ent_id;
		}
	}
	return 0;
}

stock bool:is_player_think(const player_id, const Float:think_time = 1.0)
{
	static Float:think[33];
	
	if(0 < player_id < 33)
	{
		if(think[player_id] > halflife_time()) return true;
		
		think[player_id] = halflife_time()+think_time;
	}
	return false;
}

// 0 - нет такого игрока
// 1 - игрок на сервере
// 2 - игрок на сервере и жив
stock cs_get_player_status(const player_id)
{
	if(0 < player_id < 33)
	{
		if(is_user_connected(player_id))
		{
			if(is_user_alive(player_id))
				return 2;
			return 1;
		}
	}
	return 0;
}

stock my_register_clcmd(const cmd[], const func[])
{
	new format_cmd[128], format_cmd_len = charsmax(format_cmd);
	
	register_clcmd(cmd, func);
	
	formatex(format_cmd, format_cmd_len, "say /%s", cmd);
	register_clcmd(format_cmd, func);
	
	formatex(format_cmd, format_cmd_len, "say_team /%s", cmd);
	register_clcmd(format_cmd, func);
}

stock Float:get_weapon_default_reload(const entity_id)
	return (pev_valid(entity_id) == 2 ? get_pdata_float(entity_id, 48, 4) : 0.0);
	
stock get_next_array_id(i, size)
{
	i++;
	
	if(i < size) return i;
	
	return 0;
}

stock get_prev_array_id(i, size)
{
	i--;
	
	if(i > 0 && i < size) return i;
	
	return 0;
}

stock bool:compare_array_f(Float:arr[], Float:arr2[])
{
	return (arr[0] == arr2[0] && arr[1] == arr2[1] && arr[2] == arr2[2] ? true : false);
}

stock bool:find_word_in_str(where[],const where_len = 64, const what[], const delimiter = ',')
{
	new data_left[256], data_left_charsmax = charsmax(data_left);
	while (where[0] != 0 && strtok(where, data_left, data_left_charsmax, where, where_len, delimiter))
	{
		trim(where);
		trim(data_left);
		
		if(equal(data_left, what)) return true;
	}
	return false;
}

stock Float:get_entity_distance_f(const entity_id, const entity_id2)
{
	return entity_range(entity_id, entity_id2);
}

stock set_player_bartime(const player_id, const sec = 0)
{
	static msg_bartime_id;
	if(!msg_bartime_id) msg_bartime_id = get_user_msgid("BarTime");

	message_begin(MSG_ONE, msg_bartime_id, {0,0,0}, player_id);
	write_short(sec);
	message_end();
}

// dest[] – массив куда скопируются экранированные данные
// len – длина массива dest
// src[] – исходная строка для экранирования
stock mysql_escape_string(src[], dest[], len)
{
    copy(dest, len, src);

    replace_all(dest, len, "\", "\\");
    replace_all(dest, len, "\0", "\\0");
    replace_all(dest, len, "\r", "\\r");
    replace_all(dest, len, "\n", "\\n");
    replace_all(dest, len, "\x1a", "\Z");
    replace_all(dest, len, "'", "\'");
    replace_all(dest, len, "^"", "\^"");

    return PLUGIN_HANDLED;
}

stock Float:get_entity_health_f(const entity_id, Float:entity_health = 0.0)
{
	pev(entity_id, pev_health, entity_health);
	return entity_health;
}

stock set_entity_health_f(const entity_id, Float:entity_health)
{
	set_pev(entity_id, pev_health, entity_health);
}

stock get_plcount_in_radius(const Float:origin[3], const Float:radius)
{
	new count = 0, Float:player_origin[3];

	for(new i = 1; i < 33; i++)
	{
		if(cs_get_player_status(i) == 2)
		{
			pev(i, pev_origin, player_origin);
			
			if(get_distance_f(origin, player_origin) <= radius)
				count++;
		}
	}
	return count;
}

stock bool:set_player_delay_time(Float:pthink, const Float:delay = 0.5)
{
	if(pthink >= halflife_time())
		return false;
	
	pthink = halflife_time()+delay;
	return true;
}

stock get_LastHitGroup(const player_id)
{
	return get_pdata_int(player_id, 75);
}

stock my_precache_sound(const path[])
{
	new format_str[256];
	if(containi(path, ".mp3") != -1)
	{
		formatex(format_str, 255, "sound/%s", path);
		precache_generic(format_str);
	}
	else
		precache_sound(path);
}

stock my_play_sound_cmd(const player_id, const path[])
{
	if(containi(path, ".mp3") != -1)
		client_cmd(player_id, "mp3 play sound/%s", path);
	else
		client_cmd(player_id, "spk %s", path);
}

stock set_pl_weapon_model(const player_id, const model_id = 'p', const modelpath[])
{
	switch(model_id)
	{
		case 'v': { entity_set_string(player_id, EV_SZ_viewmodel, modelpath); }
		case 'p': { entity_set_string(player_id, EV_SZ_weaponmodel, modelpath); }
	}
}

stock get_weapon_slot(const classname[])
{
	return WEAPON_SLOT[get_weaponid(classname)];
}

stock my_give_item(const player_id, const classname[], const prevdrop = 0)
{
	if(prevdrop)
	{
		new slot = get_weapon_slot(classname);
		if(slot == 1 || slot == 2)
			drop_weapons_in_slot(const player_id, const slot)
	}
	give_item(player_id, classname);
}

stock get_speed_vector(const Float:origin[3], const Float:origin_victim[3], Float:speed, Float:new_velocity[3])
{
	new_velocity = origin_victim;
	
	new_velocity[0] -= origin[0];
	new_velocity[1] -= origin[1];
	new_velocity[2] -= origin[2];

	new Float:num; num = floatsqroot(speed*speed / (new_velocity[0]*new_velocity[0] + new_velocity[1]*new_velocity[1] + new_velocity[2]*new_velocity[2]))
	
	xs_vec_mul_scalar(new_velocity, num, new_velocity);
}

stock get_plarr_in_radius(const Float:origin[3], const Float:radius, player_list[], const CsTeams:team = CS_TEAM_UNASSIGNED)
{
	new player_id = FM_NULLENT, i;
	while((player_id = engfunc(EngFunc_FindEntityInSphere, player_id, origin, radius)) != 0)
	{
		if(cs_get_player_status(player_id) == 2)
		{
			if(!is_entity_point_visible(player_id, origin)) continue;
			
			if(team != CS_TEAM_UNASSIGNED && cs_get_user_team(player_id) != team) continue;

			player_list[i++] = player_id;
		}
	}
	
	return i;
}

stock get_entarr_in_radius(const Float:origin[3], const Float:radius, entity_list[], const team = 0)
{
	new entity_id = FM_NULLENT, i;
	while((player_id = engfunc(EngFunc_FindEntityInSphere, entity_id, origin, radius)) != 0)
	{
		if(is_valid_ent(entity_id))
		{
			if(!is_entity_point_visible(entity_id, origin)) continue;
			
			if(team && pev(entity_id, pev_team) != team) continue;

			entity_list[i++] = entity_id;
		}
	}
	
	return i;
}

// Проверяет является ли точка видимой от начало до нее. Игнорируются стекло, игроки и ignore_ent. 
stock bool:is_entity_visible(const entity_id, const target_id, ignore_ent = 0) 
{ 
	new Float:origin_start[3], Float:origin_end[3];
	
	pev(entity_id, pev_origin, origin_start);
	pev(target_id, pev_origin, origin_end);

	engfunc(EngFunc_TraceLine, origin_start, origin_end, IGNORE_GLASS | IGNORE_MONSTERS, ignore_ent, 0) 

	static Float:fraction 
	get_tr2(0, TR_flFraction, fraction) 

	// Возвращение результата
	// Если 1, значит мы ничего не коснулись 
	return (fraction == 1.0)
}
stock bool:is_entity_point_visible(const entity_id, const Float:origin_end[3], ignore_ent = 0) 
{ 
	new Float:origin_start[3];
	
	pev(entity_id, pev_origin, origin_start);

	engfunc(EngFunc_TraceLine, origin_start, origin_end, IGNORE_GLASS | IGNORE_MONSTERS, ignore_ent, 0) 

	static Float:fraction 
	get_tr2(0, TR_flFraction, fraction) 

	// Возвращение результата
	// Если 1, значит мы ничего не коснулись 
	return (fraction == 1.0)
} 

stock bool:set_player_origin(const player_id, Float:origin[3], const sprite_id = 0, const sprite_end_id = 0, const soundpath[] = "", const hull = HULL_POINT)
{
	#define	CONTENTS_SKY	-6
	
	if((engfunc(EngFunc_PointContents, origin) == CONTENTS_SKY)) return false;

	new Float:player_origin[3];
	pev(player_id, pev_origin, player_origin);

	if(sprite_id)
	{
		message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
		{
			write_byte(TE_SPRITETRAIL) // TE ID
			write_coord_f(player_origin[0]) // Start Position X
			write_coord_f(player_origin[1]) // Start Position Y
			write_coord_f(player_origin[2]+ 40.0) // Start Position Z
			write_coord_f(player_origin[0]) // End Position X
			write_coord_f(player_origin[1]) // End Position Y
			write_coord_f(player_origin[2]) // End Position Z
			write_short(sprite_id) // Sprite Index
			write_byte(30) // Count
			write_byte(10) // Life
			write_byte(1) // Scale
			write_byte(50) // Velocity Along Vector
			write_byte(10) // Rendomness of Velocity
		}
		message_end();
	}

	if(soundpath[0])
		emit_sound(player_id, CHAN_STATIC, soundpath, VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
	
	// Player cannot stuck in the wall/floor
	origin[0] += ((origin[0] - player_origin[0] > 0) ? -50.0 : 50.0);
	origin[1] += ((origin[1] - player_origin[1] > 0) ? -50.0 : 50.0);
	origin[2] += 40.0;
	
	if((engfunc(EngFunc_PointContents, origin) == CONTENTS_SKY)) return false;
	if(!is_hull_vacant(origin, hull)) return false;

	if(sprite_end_id)
	{
		message_begin(MSG_BROADCAST, SVC_TEMPENTITY);
		{
			write_byte(TE_SPRITETRAIL) // TE ID
			write_coord_f(origin[0]) // Start Position X
			write_coord_f(origin[1]) // Start Position Y
			write_coord_f(origin[2]+ 40.0) // Start Position Z
			write_coord_f(origin[0]) // End Position X
			write_coord_f(origin[1]) // End Position Y
			write_coord_f(origin[2]) // End Position Z
			write_short(sprite_end_id) // Sprite Index
			write_byte(30) // Count
			write_byte(10) // Life
			write_byte(1) // Scale
			write_byte(50) // Velocity Along Vector
			write_byte(10) // Rendomness of Velocity
		}
		message_end();
	}
	
	// Teleport player
	set_pev(player_id, pev_origin, origin);
	
	return true;
}

stock set_entity_velocity(const entity_id, Float:velocity[3], Float:avelocity[3] = {0.0, 0.0, 0.0})
{
	set_pev(entity_id, pev_velocity, velocity);
	
	if(avelocity[0] != 0.0 || avelocity[1] != 0.0 || avelocity[2] != 0.0)
		set_pev(entity_id, pev_avelocity, avelocity);
}

stock ham_give_weapon(const player_id, const classname[])
{
	if(!equal(classname, "weapon_", 7)) return 0;

	//entity_id = engfunc(EngFunc_CreateNamedEntity,engfunc(EngFunc_AllocString, classname));
	new entity_id = create_entity(classname);
	if(pev_valid(entity_id) != 2) return 0;

	set_pev(entity_id, pev_spawnflags, SF_NORESPAWN);
	dllfunc(DLLFunc_Spawn, entity_id);

	if(!ExecuteHam(Ham_AddPlayerItem, player_id, entity_id))
	{
		if(pev_valid(entity_id) == 2) 
			set_pev(entity_id,pev_flags,pev(entity_id,pev_flags) | FL_KILLME);
			//remove_entity(entity_id);

		return 0;
	}

	ExecuteHam(Ham_Item_AttachToPlayer, entity_id, player_id);
	
	emit_sound(player_id, CHAN_BODY, "items/gunpickup2.wav", VOL_NORM, ATTN_NORM, 0, PITCH_NORM);
	
	return entity_id;
}

stock bool:equal_msg_arg_string(const arg_num, const str[]) 
{
    new str_temp[33];
    get_msg_arg_string(arg_num, str_temp, 32);
    
    return ( equal(str_temp, str) ? true : false );
}

stock bool:nvault_query(const player_id, const nvault_name[], nvault_data[], const nvault_data_len = 0, const mode = 0, const prefix[] = "", const del_days = 7)
{
	new nvault_handle = nvault_open(nvault_name);

	if(nvault_handle != INVALID_HANDLE)
	{	
		new 
			player_steam_id[128],
			timestamp;
		
		get_user_authid(player_id, player_steam_id, charsmax(player_steam_id));

		if(prefix[0])
			add(player_steam_id, charsmax(player_steam_id), prefix);

		if(!mode)
		{
			nvault_prune(nvault_handle, 0, get_systime() - (86400 * del_days));
			nvault_lookup(nvault_handle, player_steam_id, nvault_data, nvault_data_len, timestamp);
		}
		else
		{
			if(!nvault_data[0]) nvault_remove(nvault_handle, player_steam_id);
			else 				nvault_set(nvault_handle, player_steam_id, nvault_data);
		}
		/* Вот так будет зависать сервера -_- */
		// nvault_set(nvault_handle, player_steam_id, ""); 
		
		nvault_close(nvault_handle);
	}
	else
		return false;
	
	return true;
}

stock bool:find_player_flags(const player_id, const flags[] = "", flags_bitsum = 0)
{
	new player_flags_bitsum = get_user_flags(player_id);
	
	if(flags[0])
	{
		flags_bitsum = read_flags(flags);
		return (player_flags_bitsum & flags_bitsum == flags_bitsum ? true : false);
	}
	else
		return (player_flags_bitsum & flags_bitsum == flags_bitsum ? true : false);
		
	return false;
}

// the dot product is performed in 2d, making the view cone infinitely tall
stock bool:is_in_viewcone_v2(index, const Float:point[3])
{
	new Float:angles[3];
	pev(index, pev_angles, angles);
	engfunc(EngFunc_MakeVectors, angles);
	global_get(glb_v_forward, angles);
	angles[2] = 0.0;

	new Float:origin[3], Float:diff[3], Float:norm[3];
	pev(index, pev_origin, origin);
	xs_vec_sub(point, origin, diff);
	diff[2] = 0.0;
	xs_vec_normalize(diff, norm);

	new Float:dot, Float:fov;
	dot = xs_vec_dot(norm, angles);
	pev(index, pev_fov, fov);
	if (dot >= floatcos(fov * M_PI / 360))
		return true;
	return false;
}

stock equal_in_array (const str[], const arr[][], const arr_sizeof)
{
	for (new  i = 0; i < arr_sizeof; i++)
		if(equal(str, arr[i])) return true;

	return false;
}

/* Своя функция нанесения урона */
stock take_damage(const victim_id, const attacker_id, const inflictor_id, const damage, const weapon[], const money = 300, &killed = 0, bodypart = 0)
{
	static
		msg_damage_id,
		msg_deathmsg_id,

		is_player,
		victim_hp_next,
		Float:red_arrow_origin[3];

	is_player = cs_get_player_status(victim_id);
	
	killed = 0;

	if(!msg_deathmsg_id) msg_deathmsg_id = get_user_msgid("DeathMsg");
	if(!msg_damage_id) msg_damage_id = get_user_msgid("Damage");
	
	if(is_player == 2 || (!is_player && pev_valid(victim_id) == 2))
	{
		victim_hp_next 	= (!is_player ? pev(victim_id, pev_health)-damage : get_user_health(victim_id)-damage);

		if(victim_hp_next <= 0)
		{
			// Чем был убил
			set_pev(victim_id, pev_dmg_inflictor, inflictor_id);

			// Выдаем деньги за убийство
			if(cs_get_player_status(attacker_id) && money)
				cs_set_user_money(attacker_id, (cs_get_user_money(attacker_id)+money), 1);

			// Если жертва объект
			if(!is_player)
			{
				killed = 1;
				
				ExecuteHamB(Ham_TakeDamage, victim_id, 0, attacker_id, float(damage), DMG_BULLET);
			}
			else
			{
				set_msg_block(msg_deathmsg_id, BLOCK_ONCE);
				
				ExecuteHamB(Ham_Killed, victim_id, attacker_id, 3);
				
				message_begin(MSG_BROADCAST, msg_deathmsg_id);
				{
					write_byte(attacker_id); // аттакующий
					write_byte(victim_id); // жертва
					write_byte(bodypart); // убийство в голову (0 - нет, 1 - да)
					write_string(weapon); // орудие убийства
				}
				message_end();
			}

			killed = 1;
		}
		else if(victim_hp_next > 0)
		{
			set_pev(victim_id, pev_health, float(victim_hp_next));
			
			/* Показ урона и направления урона */
			if(is_a_player(victim_id))
			{
				pev(( pev_valid(attacker_id) == 2 ? attacker_id : victim_id ), pev_origin, red_arrow_origin);

				message_begin(MSG_ONE, msg_damage_id, _, victim_id);
				{
					write_byte(victim_hp_next);
					write_byte(get_user_armor(victim_id));
					write_long(DMG_BULLET);
					write_coord_f(red_arrow_origin[0]);
					write_coord_f(red_arrow_origin[1]);
					write_coord_f(red_arrow_origin[2]);
				}
				message_end();
			}
		}
	}
}

stock get_player_playable_count()
{
	new count = 0, i = 1;
	for(; i < 33; i++)
		if(cs_get_player_status(i) && is_playable_team(get_player_team(i))) count++;
	
	return count;
}

stock bool:set_weaponlist_message(const player_id, const csw_id, const WeaponName[] = "-2", PrimaryAmmoID = -2, PrimaryAmmoMaxAmount = -2, SecondaryAmmoID = -2, SecondaryAmmoMaxAmount = -2, SlotID = -2, NumberInSlot = -2, WeaponID = -2, Flags = -2)
{
	if(0 < csw_id < 31)
	{
		new WeaponNameNew[64];
		
		if(WeaponName[0] == '-')
			formatex(WeaponNameNew, 63, info_wlist_message(csw_id, em_wlist_WeaponName));
		else
			formatex(WeaponNameNew, 63, WeaponName);

		if(PrimaryAmmoID < -1)
			PrimaryAmmoID = info_wlist_message(csw_id, em_wlist_PrimaryAmmoID);
		if(PrimaryAmmoMaxAmount < -1)
			PrimaryAmmoMaxAmount = info_wlist_message(csw_id, em_wlist_PrimaryAmmoMaxAmount);
		if(SecondaryAmmoID < -1)
			SecondaryAmmoID = info_wlist_message(csw_id, em_wlist_SecondaryAmmoID);
		if(SecondaryAmmoMaxAmount < -1)
			SecondaryAmmoMaxAmount = info_wlist_message(csw_id, em_wlist_SecondaryAmmoMaxAmount);
		if(SlotID < -1)
			SlotID = info_wlist_message(csw_id, em_wlist_SlotID);
		if(NumberInSlot < -1)
			NumberInSlot = info_wlist_message(csw_id, em_wlist_NumberInSlot);
		if(WeaponID < -1)
			WeaponID = info_wlist_message(csw_id, em_wlist_WeaponID);
		if(Flags < -1)
			Flags = info_wlist_message(csw_id, em_wlist_Flags);
		
		message_begin(MSG_ONE, 78, _, player_id);
		{
			write_string(WeaponNameNew);
			write_byte(PrimaryAmmoID);
			write_byte(PrimaryAmmoMaxAmount);
			write_byte(SecondaryAmmoID);
			write_byte(SecondaryAmmoMaxAmount);
			write_byte(SlotID);
			write_byte(NumberInSlot);
			write_byte(WeaponID);
			write_byte(Flags);
		}
		message_end();
		
		return true;
	}
	return false;
}

stock get_weaponid_v2(const classname[])
{
	for(new i = 0; i <= CSW_P90; i++)
		if(equal(WEAPON_CLASSNAME[i], classname)) return i;
	
	return 0;
}

/* takes a weapon from a player efficiently */
stock ham_strip_weapon(id,weapon[])
{
    if(!equal(weapon,"weapon_",7)) return 0;

    new wId = get_weaponid(weapon);
    if(!wId) return 0;

    new wEnt;
    while((wEnt = engfunc(EngFunc_FindEntityByString,wEnt,"classname",weapon)) && pev(wEnt,pev_owner) != id) {}
    if(!wEnt) return 0;

    if(get_user_weapon(id) == wId) ExecuteHamB(Ham_Weapon_RetireWeapon,wEnt);

    if(!ExecuteHamB(Ham_RemovePlayerItem,id, wEnt)) return 0;
    ExecuteHamB(Ham_Item_Kill, wEnt);

    set_pev(id,pev_weapons,pev(id,pev_weapons) & ~(1<<wId));

    // this block should be used for Counter-Strike:
    if(wId == CSW_C4)
    {
        cs_set_user_plant(id,0,0);
        cs_set_user_bpammo(id,CSW_C4,0);
    }
    else if(wId == CSW_SMOKEGRENADE || wId == CSW_FLASHBANG || wId == CSW_HEGRENADE)
        cs_set_user_bpammo(id,wId,0);

    return 1;
}

stock get_floor_origin (Float:start[3])
{
	new tr, Float:end[3]
	end[0] = start[0]
	end[1] = start[1]
	end[2] = -99999.9
	engfunc(EngFunc_TraceLine, start, end, DONT_IGNORE_MONSTERS, -1, tr)
	get_tr2(tr, TR_vecEndPos, start)
}

stock get_team_score (score[])
{
	arrayset(score, 0, _:CS_TEAM_SPECTATOR+1);
	
	for(new i = 1; i < 33; i++)
		if(is_user_connected(i))
			score[_:cs_get_user_team(i)] += get_user_frags(i);
}

stock get_team_frags( team )
{
    new team_frags = 0;
    for( new id = 1; id < 33; id++ )
    {
        if( get_entity_team(id) == team )
            team_frags += get_user_frags(id);
    }
    return team_frags;
}

/* Проверка существования файла с возможностями как у formatex */
stock file_exists_v2( const file[], any:... )
{
	new path[256];
	
	vformat(path, charsmax(path), file, 2);
	
	return file_exists(path);
}

bool:find_player_flags_v2(const id, const flags[] = "", flags_bitsum = 0, const bool:strict = true)
{
	new player_flags_bitsum = get_user_flags(id);
	
	if(flags[0])
		flags_bitsum = read_flags(flags);

	if( strict )
		return (player_flags_bitsum & flags_bitsum == flags_bitsum ? true : false);
	else
		return (player_flags_bitsum & flags_bitsum ? true : false);
		
	return false;
}

get_players_count( const team[], const flags[] = "ae" )
{
    new players[32], num;
    get_players(players, num, flags, team); // "ae" - флаги для живых игроков и указанной команды
    return num;
}

#if cellbits == 32
	#define OFFSET_CSMONEY  115
#else
	#define OFFSET_CSMONEY  140
#endif

#define OFFSET_LINUX       5

/* Значение money с минусом означает что нужно отнять */
stock cs_set_user_money_v2( const id, money, const flash = 1 )
{
	money += cs_get_user_money_v2(id);
	
	set_pdata_int(id, OFFSET_CSMONEY, money, OFFSET_LINUX);
	
	/* if( !flash ) return; */
	
	message_begin(MSG_ONE, get_user_msgid("Money"), {0,0,0}, id);
	{
		write_long(money);
		write_byte(flash);
	}
	message_end();
}

stock cs_get_user_money_v2( const id )
{
	return get_pdata_int(id, OFFSET_CSMONEY, OFFSET_LINUX);
}
```
