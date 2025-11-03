#!/usr/bin/env python3
import json
import sys

try:
    from scapy.all import get_if_list, get_if_hwaddr, conf
    from scapy.arch import get_windows_if_list

    # الحصول على قائمة الواجهات مع تفاصيلها (لويندوز)
    # هذا يعطينا الوصف الصديق للمستخدم (e.g., "Wi-Fi")
    try:
        win_interfaces = get_windows_if_list()
        # إنشاء قاموس للبحث السريع عن الوصف باستخدام GUID
        description_map = {iface.get('guid'): iface.get('description') for iface in win_interfaces}
    except ImportError:
        # في حال عدم وجود Npcap أو بيئة غير ويندوز
        description_map = {}

    interfaces = []
    for iface in get_if_list():
        # تجاهل الواجهات الوهمية والـ Loopback
        if 'Loopback' in iface or (sys.platform == "win32" and not iface.startswith('\\Device\\NPF_')):
            continue

        description = 'Unknown Interface'
        if sys.platform == "win32" and '{' in iface:
            guid = '{' + iface.split('{')[1]
            description = description_map.get(guid, iface)

        interfaces.append({
            'name': iface,  # اسم الجهاز الكامل مثل \Device\NPF_{...}
            'description': description, # الوصف الصديق مثل "Wi-Fi"
            'mac_address': get_if_hwaddr(iface)
        })

    print(json.dumps(interfaces))
except Exception as e:
    # Fallback
    print(json.dumps([
        {'name': conf.iface, 'description': 'Default Interface'}
    ]))
    sys.exit(0)