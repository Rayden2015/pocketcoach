import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:pocket_coach_mobile/providers/engagement_providers.dart';
import 'package:pocket_coach_mobile/screens/catalog_screen.dart';
import 'package:pocket_coach_mobile/screens/learning_home_screen.dart';
import 'package:pocket_coach_mobile/screens/profile_screen.dart';

class MainTabsScreen extends ConsumerStatefulWidget {
  const MainTabsScreen({super.key});

  @override
  ConsumerState<MainTabsScreen> createState() => _MainTabsScreenState();
}

class _MainTabsScreenState extends ConsumerState<MainTabsScreen> {
  var _index = 0;

  @override
  Widget build(BuildContext context) {
    final unread = ref.watch(unreadNotificationCountProvider);

    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: const [
          CatalogScreen(),
          LearningHomeScreen(),
          ProfileScreen(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.menu_book_outlined),
            selectedIcon: Icon(Icons.menu_book),
            label: 'Catalog',
          ),
          NavigationDestination(
            icon: Icon(Icons.school_outlined),
            selectedIcon: Icon(Icons.school),
            label: 'Learning',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.small(
        onPressed: () {
          ref.invalidate(notificationsProvider);
          ref.invalidate(unreadNotificationCountProvider);
          context.push('/notifications');
        },
        tooltip: 'Notifications',
        child: unread.when(
          loading: () => const Icon(Icons.notifications_outlined),
          error: (_, __) => const Icon(Icons.notifications_outlined),
          data: (count) => Badge(
            isLabelVisible: count > 0,
            label: Text(count > 99 ? '99+' : '$count'),
            child: const Icon(Icons.notifications_outlined),
          ),
        ),
      ),
    );
  }
}
