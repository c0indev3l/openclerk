<?php

/**
 * Issue #194: This page displays all of your transactions with tabs and the current values of each
 * type of balance.
 */

require(__DIR__ . "/../inc/global.php");
require_login();

require(__DIR__ . "/../layout/templates.php");

$user = get_user(user_id());

$page_size = 50;

require(__DIR__ . "/_profile_common.php");

page_header("Your Transactions", "page_your_transactions", array('js' => array('accounts'), 'class' => 'report_page'));

// get all possible exchanges and currencies
$q = db()->prepare("SELECT exchange FROM transactions WHERE user_id=? GROUP BY exchange");
$q->execute(array(user_id()));
$exchanges = $q->fetchAll();

// get all possible exchanges and currencies
$q = db()->prepare("SELECT currency1 AS currency FROM transactions WHERE user_id=? GROUP BY currency1");
$q->execute(array(user_id()));
$currencies = $q->fetchAll();

$page_args = array(
	'skip' => max(0, (int) require_get("skip", 0)),
	'exchange' => require_get('exchange', false),
	'currency' => require_get('currency', false),
);

// TODO implement filtering
$extra_query = "";
$extra_args = array();

if ($page_args['exchange']) {
	$extra_query .= " AND exchange=?";
	$extra_args[] = $page_args['exchange'];
}

if ($page_args['currency']) {
	$extra_query .= " AND (currency1=? OR currency2=?)";
	$extra_args[] = $page_args['currency'];
	$extra_args[] = $page_args['currency'];
}

$q = db()->prepare("SELECT * FROM transactions WHERE user_id=? $extra_query ORDER BY transaction_date_day DESC LIMIT " . $page_args['skip'] . ", $page_size");
$q->execute(array_merge(array(user_id()), $extra_args));
$transactions = $q->fetchAll();

?>

<!-- page list -->
<?php
$page_id = -1;
$your_transactions = true;
require(__DIR__ . "/_profile_pages.php");
?>

<div style="clear:both;"></div>

<div class="transaction-introduction">
	<div class="transaction-filter">
		<h2>Filter Transactions</h2>

		<form action="<?php echo htmlspecialchars(url_for('your_transactions')); ?>" method="get">
		<table class="standard">
		<tr>
			<th>Account Type</th>
			<td>
				<select name="exchange">
					<option value="">(all)</option>
					<?php
					foreach ($exchanges as $exchange) {
						echo "<option value=\"" . htmlspecialchars($exchange['exchange']) . "\"" . ($page_args['exchange'] == $exchange['exchange'] ? " selected" : "") . ">" . htmlspecialchars(get_exchange_name($exchange['exchange'])) . "</option>\n";
					} ?>
				</select>
			</td>
		</tr>
		<tr>
			<th>Transaction currency</th>
			<td>
				<select name="currency">
					<option value="">(all)</option>
					<?php
					foreach ($currencies as $currency) {
						echo "<option value=\"" . htmlspecialchars($currency['currency']) . "\"" . ($page_args['currency'] == $currency['currency'] ? " selected" : "") . ">" . htmlspecialchars(get_currency_abbr($currency['currency'])) . "</option>\n";
					} ?>
				</select>
			</td>
		</tr>
		<tr class="buttons">
			<td colspan="2">
				<input type="submit" value="Filter">
			</td>
		</tr>
		</table>
		</form>
	</div>

	<h1>Your Transactions</h1>

	<p>
		This is a draft version of a page which will allow you to see the historical changes to your various accounts over time as daily transactions,
		generated automatically by <?php echo htmlspecialchars(get_site_config('site_name')); ?>.
	</p>

	<p>
		In the future, you will be able to edit or delete these transactions, create your own transactions, or export these transactions to CSV.
	</p>
</div>

<span style="display:none;" id="sort_buttons_template">
<!-- heading sort buttons -->
<span class="sort_up" title="Sort ascending">Asc</span><span class="sort_down" title="Sort descending">Desc</span>
</span>

<div class="your-transactions">
<table class="standard standard_account_list">
<thead>
	<tr>
		<th class="balance default_sort_down">Date</th>
		<th class="">Account</th>
		<th class="">Description</th>
		<th class="number">Amount</th>
	</tr>
</thead>
<tbody>
<?php
$count = 0;
foreach ($transactions as $transaction) {
	?>
	<tr class="<?php echo $count % 2 == 0 ? "odd" : "even"; ?>">
		<td><?php echo "<span title=\"" . htmlspecialchars(date('Y-m-d H:i:s', strtotime($transaction['transaction_date']))) . "\">" . date("Y-m-d", strtotime($transaction['transaction_date'])) . "</span>"; ?></td>
		<td><?php echo get_exchange_name($transaction['exchange']); ?></td>
		<td>
			<?php if ($transaction['is_automatic']) { ?>
			(generated automatically)
			<?php } ?>
		</td>
		<td class="number<?php echo $transaction['value1'] < 0 ? " negative" : ""; ?>">
			<span class="transaction_<?php echo htmlspecialchars($transaction['currency1']); ?>">
				<?php echo currency_format($transaction['currency1'], $transaction['value1'], 8); ?>
			</span>
		</td>
	</tr>
<?php } ?>
<?php if (!$transactions) { ?>
	<tr><td colspan="4"><i>No transactions found.</td></tr>
<?php } ?>
</tbody>
<tfoot>
	<tr>
		<td class="buttons" colspan="4">
			<form action="<?php echo htmlspecialchars(url_for('your_transactions')); ?>" method="get">
				<?php
				$button_args = array('skip' => max(0, $page_args['skip'] - $page_size)) + $page_args;
				foreach ($button_args as $key => $value) {
					echo "<input type=\"hidden\" name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($value) . "\">\n";
				} ?>
				<input type="submit" class="button-previous" value="&lt; Previous"<?php echo $page_args['skip'] > 0 ? "" : " disabled"; ?>>
			</form>

			<form action="<?php echo htmlspecialchars(url_for('your_transactions')); ?>" method="get">
				<?php
				$button_args = array('skip' => max(0, $page_args['skip'] + $page_size)) + $page_args;
				foreach ($button_args as $key => $value) {
					echo "<input type=\"hidden\" name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($value) . "\">\n";
				} ?>
				<input type="submit" class="button-next" value="Next &gt;"<?php echo count($transactions) == $page_size ? "" : " disabled"; ?>>
			</form>
		</td>
	</tr>
</tfoot>
</table>
</div>

<?php

page_footer();