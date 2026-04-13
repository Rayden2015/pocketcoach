import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/auth/google_sign_in_helper.dart';
import 'package:pocket_coach_mobile/config/api_config.dart';
import 'package:pocket_coach_mobile/providers/app_features_provider.dart';
import 'package:pocket_coach_mobile/providers/session_provider.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _password2 = TextEditingController();

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _password2.dispose();
    super.dispose();
  }

  Future<void> _google() async {
    await signInWithGoogle(ref, context);
  }

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (_password.text != _password2.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Passwords do not match')),
      );
      return;
    }
    await ref.read(sessionProvider.notifier).register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          password: _password.text,
          passwordConfirmation: _password2.text,
        );

    if (!mounted) {
      return;
    }

    final s = ref.read(sessionProvider);
    if (s.hasError) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Registration failed: ${s.error}')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(sessionProvider);
    final busy = session.isLoading;
    final googleOn = ref.watch(googleSignInEnabledProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Create account')),
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
          if (googleOn) ...[
            OutlinedButton.icon(
              onPressed: busy ? null : _google,
              icon: const Icon(Icons.login),
              label: const Text('Sign up with Google'),
            ),
            const SizedBox(height: 12),
            Text(
              'or register with email',
              style: Theme.of(context).textTheme.labelSmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
          ],
          TextField(
            controller: _name,
            enabled: !busy,
            textCapitalization: TextCapitalization.words,
            decoration: const InputDecoration(
              labelText: 'Name',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
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
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _password2,
            enabled: !busy,
            obscureText: true,
            decoration: const InputDecoration(
              labelText: 'Confirm password',
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
                : const Text('Register'),
          ),
          const SizedBox(height: 16),
          TextButton(
            onPressed: busy ? null : () => context.go('/login'),
            child: const Text('Already have an account? Sign in'),
          ),
        ],
      ),
    );
  }
}
