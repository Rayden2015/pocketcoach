import 'package:pocket_coach_mobile/config/api_config.dart';
import 'package:url_launcher/url_launcher.dart';

/// Opens a path on the Laravel web app (not `/api`), e.g. `/my-space/coach`.
Future<bool> openTenantWebPath(String path) async {
  final base = ApiConfig.webBaseUrl;
  final normalized = path.startsWith('/') ? path : '/$path';
  final uri = Uri.parse('$base$normalized');
  return launchUrl(uri, mode: LaunchMode.externalApplication);
}
