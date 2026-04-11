import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:pocket_coach_mobile/main.dart';

void main() {
  testWidgets('app builds', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: PocketCoachApp()));
    await tester.pump();
    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
