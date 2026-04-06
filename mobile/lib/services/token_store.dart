import 'package:shared_preferences/shared_preferences.dart';

const _kTokenKey = 'pocket_coach_api_token';

class TokenStore {
  Future<String?> read() async {
    final prefs = await SharedPreferences.getInstance();
    final v = prefs.getString(_kTokenKey);
    return v == null || v.isEmpty ? null : v;
  }

  Future<void> write(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_kTokenKey, token);
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_kTokenKey);
  }
}
