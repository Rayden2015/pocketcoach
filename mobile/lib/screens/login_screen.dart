import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:pocket_coach_mobile/config/api_config.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _google() async {
    if (ApiConfig.googleServerClientId.isEmpty) {
      return;
    }
    FocusScope.of(context).unfocus();
    final google = GoogleSignIn(
      serverClientId: ApiConfig.googleServerClientId,
    );
    try {
      final account = await google.signIn();
      if (account == null || !mounted) {
        return;
      }
      final auth = await account.authentication;
      final id = auth.idToken;
      if (id == null || id.isEmpty) {
        throw StateError('Google did not return an ID token. Check serverClientId matches Web OAuth client.');
      }
      await ref.read(sessionProvider.notifier).loginWithGoogleIdToken(id);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Google sign-in failed: $e')),
        );
      }
      return;
    }

    if (!mounted) {
      return;
    }
    final s = ref.read(sessionProvider);
    if (s.hasError) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sign in failed: ${s.error}')),
      );
    }
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    await ref.read(sessionProvider.notifier).login(
          email: _email.text.trim(),
          password: _password.text,
        );

    if (!mounted) {
      return;
    }

    final s = ref.read(sessionProvider);
    if (s.hasError) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sign in failed: ${s.error}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(sessionProvider);
    final busy = session.isLoading;

    return Scaffold(
      appBar: AppBar(title: const Text('Pocket Coach')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Text(
            'API: ${ApiConfig.baseUrl}',
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          ),
          const SizedBox(height: 24),
          if (ApiConfig.googleServerClientId.isNotEmpty) ...[
            OutlinedButton.icon(
              onPressed: busy ? null : _google,
              icon: const Icon(Icons.login),
              label: const Text('Continue with Google'),
            ),
            const SizedBox(height: 12),
            Text(
              'or use email',
              style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
          ],
          TextField(
            controller: _email,
            enabled: !busy,
            keyboardType: TextInputType.emailAddress,
            decoration: const InputDecoration(
              labelText: 'Email',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _password,
            enabled: !busy,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'Password',
              border: OutlineInputBorder(),
            ),
            onSubmitted: (_) => busy ? null : _submit(),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: busy ? null : _submit,
            child: busy
                ? const SizedBox(
                    height: 22,
                    width: 22,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Text('Sign in'),
          ),
        ],
      ),
    );
  }
}
