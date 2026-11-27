<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items    = ISM_URL_Redirector::read_items();
$home     = home_url();
$host     = wp_parse_url( $home, PHP_URL_HOST );
$notice   = isset( $_GET['ism_urlr_notice'] ) ? sanitize_key( $_GET['ism_urlr_notice'] ) : '';
$warn     = ! empty( $_GET['ism_urlr_warn'] );

$notices = [
	'saved'   => [ 'success', 'Saved.' ],
	'deleted' => [ 'success', 'Deleted.' ],
	'error'   => [ 'danger', 'Error: both fields are required, and the target URL must be a valid URL (or a site path like /wp-admin/).' ],
	'duplicate' => [ 'warning', 'An item with this URL to override already exists. Duplicate not saved.' ],
];
?>
<style>
/* standupcafe palette - always dark */
.ism-urlr{background:#161616;color:#F5F5F6;padding:20px 24px;border-radius:10px;max-width:1400px}
.ism-urlr h1{color:#F5F5F6}
.ism-urlr .text-muted,.ism-urlr .form-text{color:#7E8898!important}
.ism-urlr code{color:#DED1FF;background:#2A2A2A;padding:2px 6px;border-radius:4px}
.ism-urlr a{color:#1162d9}
.ism-urlr table{background:#1E2134;color:#F5F5F6}
.ism-urlr th{background:#514A64!important;color:#F5F5F6;border-color:#464646!important}
.ism-urlr td{border-color:#464646!important;color:#F5F5F6}
.ism-urlr .table-striped>tbody>tr:nth-of-type(odd){background:#1C2128;color:#F5F5F6}
.ism-urlr .table-striped>tbody>tr{color:#F5F5F6}
.ism-urlr .table-hover>tbody>tr:hover{background:rgba(222,209,255,.06);color:#F5F5F6}
.ism-urlr .form-control,.ism-urlr .form-select{background:#2A2A2A;color:#F5F5F6;border-color:#464646}
.ism-urlr .form-control:focus{background:#2A2A2A;color:#F5F5F6;border-color:#FF5925;box-shadow:0 0 0 .25rem rgba(255,89,37,.25)}
.ism-urlr .modal-content{background:#1E2134;color:#F5F5F6;border:1px solid #464646}
.ism-urlr .modal-header,.ism-urlr .modal-footer{border-color:#464646}
.ism-urlr .btn-close{filter:invert(1) grayscale(100%)}
.ism-urlr .form-check-input{background-color:#2A2A2A;border-color:#7E8898}
.ism-urlr .ism-urlr-circle{width:38px;height:38px;border-radius:50%;padding:0;font-size:20px;font-weight:700;line-height:1;display:inline-flex;align-items:center;justify-content:center}
.ism-urlr td .form-check{display:flex;justify-content:center;align-items:center;margin:0;padding:0}
.ism-urlr td .form-check-input{margin:0;position:static}
.ism-urlr .form-check-input:checked{background-color:#FF5925;border-color:#FF5925}
.ism-urlr .alert-warning{color:#F8C200;background:rgba(248,194,0,.12);border:1px solid rgba(248,194,0,.4)}
.ism-urlr .alert-success{color:#3fb950;background:rgba(63,185,80,.12);border:1px solid rgba(63,185,80,.4)}
.ism-urlr .alert-danger{color:#ff7b72;background:rgba(255,123,114,.12);border:1px solid rgba(255,123,114,.4)}
.ism-urlr .btn-close{background:transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23F5F5F6'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat}
</style>
<div class="wrap ism-urlr" style="max-width:1400px;">
	<?php $ism_logo_b64 = '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'; ?>
	<div class="d-flex justify-content-between align-items-start mb-4">
		<div>
			<h1 class="mb-1">ISM URL Redirector</h1>
			<div class="text-muted" style="font-size:13px;">Version <?php echo esc_html( ISM_URL_Redirector::VERSION ); ?></div>
		</div>
		<img src="data:image/png;base64,<?php echo $ism_logo_b64; ?>" alt="ISM" width="64" height="64">
	</div>
	<?php if ( isset( $notices[ $notice ] ) ) : ?>
		<div class="alert alert-<?php echo esc_attr( $notices[ $notice ][0] ); ?> alert-dismissible fade show">
			<?php echo esc_html( $notices[ $notice ][1] ); ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>

	<?php if ( $warn ) : ?>
		<div class="alert alert-warning alert-dismissible fade show">
			Warning: the override URL you entered belongs to a domain that does
			<strong>not match</strong> the current site domain (<?php echo esc_html( $host ); ?>).
			The item was saved — the redirect matches by URL path regardless of domain.
			<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
		</div>
	<?php endif; ?>

	<div class="d-flex justify-content-between align-items-center mb-3">
		<button type="button" class="btn btn-primary ism-urlr-circle" data-bs-toggle="modal" data-bs-target="#ismUrlrModal"
			onclick="ismUrlrFill(-1)" title="Add item" aria-label="Add item">+</button>
		<button type="button" class="btn btn-primary ism-urlr-circle" data-bs-toggle="modal" data-bs-target="#ismUrlrHelp"
			title="About / help" aria-label="Help">?</button>
	</div>

	<table class="table table-striped table-hover align-middle">
		<thead class="table-dark">
			<tr>
				<th style="width:60px;">#</th>
				<th>URL to override (current domain: <?php echo esc_html( $host ); ?>)</th>
				<th>Force redirect to</th>
				<th style="width:90px;text-align:center;">On/Off</th>
				<th style="width:160px;">Actions</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $items ) ) : ?>
			<tr><td colspan="5" class="text-center text-muted py-4">No items yet — press "+ Add item".</td></tr>
		<?php else : ?>
			<?php foreach ( $items as $i => $item ) :
				$full = preg_match( '#^https?://#i', $item['path'] ) ? $item['path'] : home_url( $item['path'] );
				?>
			<tr>
				<td><?php echo $i + 1; ?></td>
				<td>
					<a href="<?php echo esc_url( $full ); ?>" target="_blank"><?php echo esc_html( $full ); ?></a><br>
					<small class="text-muted">path: <code><?php echo esc_html( $item['path'] ); ?></code></small>
				</td>
				<td><a href="<?php echo esc_url( $item['target'] ); ?>" target="_blank"><?php echo esc_html( $item['target'] ); ?></a></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="ism_urlr_toggle">
						<input type="hidden" name="index" value="<?php echo $i; ?>">
						<?php wp_nonce_field( 'ism_urlr_toggle' ); ?>
						<div class="form-check form-switch m-0">
							<input class="form-check-input" type="checkbox" role="switch" style="cursor:pointer;"
								onchange="this.form.submit()" <?php checked( $item['enabled'], 1 ); ?>>
						</div>
					</form>
				</td>
				<td>
					<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
						data-bs-target="#ismUrlrModal" onclick='ismUrlrFill(<?php echo $i; ?>)'>Edit</button>
					<a class="btn btn-sm btn-outline-danger" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ism_urlr_delete&index=' . $i ), 'ism_urlr_delete' ) ); ?>"
						onclick="return confirm('Delete this item?');">Delete</a>
				</td>
			</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>

	<!-- Help modal -->
	<div class="modal fade" id="ismUrlrHelp" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">How ISM URL Redirector works</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">
					<p>Redirects fire at the earliest stage of the WordPress bootstrap — the
						<code>init</code> hook, priority 1.<br>
						WordPress routing, WooCommerce, the theme and other plugins never get to handle the URL.</p>
					<p>The response is a <code>302 Found</code> and the request terminates immediately.</p>
					<p>Matching is case-insensitive and ignores query strings and trailing slashes:<br>
						<code>/Buy/3840</code>, <code>/buy/3840?x=1</code> and <code>/buy/3840/</code> all match <code>/buy/3840</code>.</p>
					<p>The admin area (<code>/wp-admin/*</code>), AJAX, cron and XML-RPC requests are never intercepted.</p>
					<hr>
					<p>Configuration is stored inside the plugin folder as a CSV file — one item per line:</p>
					<p><code>enabled,path,target</code><br>
						<span class="small">1 = enabled, 0 = disabled (the on/off switch); path may be a path or full URL;
							target may be a full URL or a site path like <code>/wp-admin/</code>.</span></p>
					<p class="mb-0">File location:<br>
						<code><?php echo esc_html( ISM_URL_Redirector::csv_path() ); ?></code></p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Add / Edit modal -->
	<div class="modal fade" id="ismUrlrModal" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="ism-urlr-form">
				<input type="hidden" name="action" value="ism_urlr_save">
				<input type="hidden" name="item_index" id="ism-urlr-index" value="">
				<?php wp_nonce_field( 'ism_urlr_save' ); ?>
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="ism-urlr-title">Add item</h5>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body">
						<div class="mb-3">
							<label class="form-label">URL to override</label>
							<input type="text" class="form-control" name="override_url" id="ism-urlr-path" required
								placeholder="/?p=1 or https://<?php echo esc_attr( $host ); ?>/?p=1">
								<div class="form-text">You can enter a path (<code>/?p=1</code>) or a full URL.
									It will be shown for the current domain as <code id="ism-urlr-preview"></code></div>
						</div>
						<div class="mb-3">
							<label class="form-label">Where to force-redirect</label>
							<input type="text" class="form-control" name="target_url" id="ism-urlr-target" required
								placeholder="https://example.com or /wp-admin/">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
						<button type="submit" class="btn btn-primary">Save</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
var ISM_URLR_ITEMS = <?php echo wp_json_encode( $items ); ?>;
var ISM_URLR_HOME  = <?php echo wp_json_encode( $home ); ?>;

function ismUrlrFill(index) {
	var path = '', target = '';
	if (index >= 0 && ISM_URLR_ITEMS[index]) {
		path   = ISM_URLR_ITEMS[index].path;
		target = ISM_URLR_ITEMS[index].target;
	}
	document.getElementById('ism-urlr-index').value   = (index >= 0) ? index : '';
	document.getElementById('ism-urlr-path').value    = path;
	document.getElementById('ism-urlr-target').value  = target;
	document.getElementById('ism-urlr-title').textContent = (index >= 0) ? 'Edit item #' + (index + 1) : 'Add item';
	ismUrlrPreview();
}

function ismUrlrPreview() {
	var raw = document.getElementById('ism-urlr-path').value.trim();
	var shown = raw;
	if (raw && !/^https?:\/\//i.test(raw)) shown = ISM_URLR_HOME + (raw.charAt(0) === '/' ? raw : '/' + raw);
	document.getElementById('ism-urlr-preview').textContent = shown || '—';
}

document.getElementById('ism-urlr-path').addEventListener('input', ismUrlrPreview);

// Domain-mismatch warning before saving.
document.getElementById('ism-urlr-form').addEventListener('submit', function (e) {
	var raw = document.getElementById('ism-urlr-path').value.trim();
	if (/^https?:\/\//i.test(raw)) {
		try {
			var u = new URL(raw);
			if (u.host !== window.location.host) {
				if (!confirm('Warning: the domain "' + u.host + '" does not match the current domain "' +
					window.location.host + '". Save anyway? (The redirect matches by URL path.)')) {
					e.preventDefault();
				}
			}
		} catch (err) { /* let server-side validation handle it */ }
	}
});
</script>
